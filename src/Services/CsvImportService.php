<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Models\User;
use Carbon\Carbon;
use ConferenceDiscountEligibility\Enums\DuplicateStrategy;
use ConferenceDiscountEligibility\Enums\EligibilityType;
use ConferenceDiscountEligibility\Models\ConferenceDiscountEntitlement;
use ConferenceDiscountEligibility\Models\ConferenceDiscountImportBatch;
use ConferenceDiscountEligibility\Support\EmailNormalizer;
use ConferenceDiscountEligibility\Support\Percentage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CsvImportService
{
    private const REQUIRED_HEADERS = ['email', 'discount_percentage'];
    private const ALLOWED_HEADERS = ['email', 'discount_percentage', 'reason', 'valid_from', 'valid_until', 'notes'];
    private const MAX_ROWS = 5000;

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    /** @return array<string, mixed> */
    public function process(
        string $path,
        string $originalFilename,
        int $scheduledConferenceId,
        DuplicateStrategy $strategy,
        bool $dryRun,
    ): array {
        $this->validateFile($path, $originalFilename, $scheduledConferenceId);
        [$headers, $rows] = $this->readRows($path);
        $this->validateHeaders($headers);

        $batch = ConferenceDiscountImportBatch::query()->create([
            'scheduled_conference_id' => $scheduledConferenceId,
            'actor_user_id' => auth()->id(),
            'original_filename' => basename($originalFilename),
            'sha256' => hash_file('sha256', $path),
            'duplicate_strategy' => $strategy->value,
            'dry_run' => $dryRun,
            'status' => 'processing',
        ]);

        $reportRows = [];
        $seen = [];
        $counts = ['total_rows' => count($rows), 'accepted_rows' => 0, 'rejected_rows' => 0, 'duplicate_rows' => 0, 'updated_rows' => 0, 'ignored_rows' => 0];

        foreach ($rows as $index => $values) {
            $line = $index + 2;
            if (count($values) > count($headers)) {
                $counts['rejected_rows']++;
                $reportRows[] = [
                    'status' => 'rejected',
                    'line' => $line,
                    'email' => (string) ($values[0] ?? ''),
                    'errors' => ['too_many_columns'],
                ];
                continue;
            }
            $record = array_combine($headers, array_pad($values, count($headers), ''));
            if (! is_array($record)) {
                $counts['rejected_rows']++;
                $reportRows[] = ['status' => 'rejected', 'line' => $line, 'email' => '', 'errors' => ['invalid_column_count']];
                continue;
            }
            $result = $this->validateRow($record, $line);
            $email = $result['normalized_email'] ?? null;
            if ($email !== null && isset($seen[$email])) {
                $result = ['status' => 'rejected', 'line' => $line, 'email' => $record['email'] ?? '', 'errors' => ['duplicate_in_file']];
                $counts['duplicate_rows']++;
            }
            if ($email !== null) { $seen[$email] = true; }

            if (($result['status'] ?? null) !== 'accepted') {
                $counts['rejected_rows']++;
                $reportRows[] = $result;
                continue;
            }

            $existing = ConferenceDiscountEntitlement::query()
                ->where('scheduled_conference_id', $scheduledConferenceId)
                ->where('eligibility_type', EligibilityType::Email->value)
                ->where('normalized_email', $email)
                ->first();
            if ($existing !== null) {
                $counts['duplicate_rows']++;
                if ($strategy === DuplicateStrategy::Error) {
                    $counts['rejected_rows']++;
                    $result['status'] = 'rejected';
                    $result['errors'] = ['already_exists'];
                    $reportRows[] = $result;
                    continue;
                }
                if ($strategy === DuplicateStrategy::Ignore) {
                    $counts['ignored_rows']++;
                    $result['status'] = 'ignored';
                    $reportRows[] = $result;
                    continue;
                }
            }

            if (! $dryRun) {
                DB::transaction(function () use ($result, $existing, $scheduledConferenceId, $batch, &$counts): void {
                    $user = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$result['normalized_email']])->first();
                    $attributes = [
                        'scheduled_conference_id' => $scheduledConferenceId,
                        'eligibility_type' => EligibilityType::Email->value,
                        'user_id' => $user?->getKey(),
                        'original_email' => $result['original_email'],
                        'percentage_basis_points' => $result['percentage_basis_points'],
                        'reason' => $result['reason'],
                        'notes' => $result['notes'],
                        'valid_from' => $result['valid_from'],
                        'valid_until' => $result['valid_until'],
                        'active' => true,
                        'source_type' => 'csv',
                        'source_reference' => (string) $batch->getKey(),
                        'updated_by' => auth()->id(),
                    ];
                    if ($existing !== null) {
                        $existing->fill($attributes)->save();
                        $counts['updated_rows']++;
                    } else {
                        $attributes['created_by'] = auth()->id();
                        ConferenceDiscountEntitlement::query()->create($attributes);
                    }
                });
            } elseif ($existing !== null && $strategy === DuplicateStrategy::Update) {
                $counts['updated_rows']++;
            }
            $counts['accepted_rows']++;
            $result['status'] = $dryRun ? 'accepted_dry_run' : ($existing ? 'updated' : 'created');
            $reportRows[] = $result;
        }

        $status = $dryRun ? 'dry_run_complete' : 'complete';
        $report = ['headers' => $headers, 'rows' => $reportRows, 'counts' => $counts];
        $batch->update([...$counts, 'status' => $status, 'report' => $report]);
        $this->auditLogger->log(
            action: 'csv_import_completed',
            scheduledConferenceId: $scheduledConferenceId,
            auditable: $batch,
            newValues: $counts,
            context: ['dry_run' => $dryRun, 'duplicate_strategy' => $strategy->value, 'sha256' => $batch->sha256],
            origin: 'csv_import',
        );
        return ['batch_id' => $batch->getKey(), 'status' => $status, ...$report];
    }

    private function validateFile(string $path, string $filename, int $scheduledConferenceId): void
    {
        if (! is_file($path) || ! is_readable($path)) { throw new RuntimeException('CSV file is not readable.'); }
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'csv') { throw new RuntimeException('Only .csv files are accepted.'); }
        $size = filesize($path);
        if ($size === false || $size < 1 || $size > (int) $this->settings->forConference($scheduledConferenceId)->csv_max_bytes) {
            throw new RuntimeException('CSV file size is invalid.');
        }
        $head = file_get_contents($path, false, null, 0, min(4096, $size));
        if ($head === false || str_contains($head, "\0")) { throw new RuntimeException('CSV contains binary or null-byte data.'); }
        if (! mb_check_encoding($head, 'UTF-8')) { throw new RuntimeException('CSV must be UTF-8 encoded.'); }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (! in_array($mime, ['text/plain','text/csv','application/csv','application/vnd.ms-excel','application/octet-stream'], true)) {
            throw new RuntimeException('CSV MIME type is not allowed.');
        }
    }

    /** @return array{0:list<string>,1:list<list<string>>} */
    private function readRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) { throw new RuntimeException('Unable to open CSV.'); }
        try {
            $rawHeaders = fgetcsv($handle);
            if (! is_array($rawHeaders)) { throw new RuntimeException('CSV header is missing.'); }
            $headers = array_map(static fn ($value): string => strtolower(trim((string) $value, " \t\n\r\0\x0B\xEF\xBB\xBF")), $rawHeaders);
            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                if (count($rows) >= self::MAX_ROWS) { throw new RuntimeException('CSV exceeds the 5000-row limit.'); }
                if (count($row) === 1 && trim((string) $row[0]) === '') { continue; }
                $rows[] = array_map(static fn ($value): string => trim((string) $value), $row);
            }
            return [$headers, $rows];
        } finally {
            fclose($handle);
        }
    }

    /** @param list<string> $headers */
    private function validateHeaders(array $headers): void
    {
        foreach (self::REQUIRED_HEADERS as $required) {
            if (! in_array($required, $headers, true)) { throw new RuntimeException("Missing required CSV header: {$required}"); }
        }
        foreach ($headers as $header) {
            if (! in_array($header, self::ALLOWED_HEADERS, true)) { throw new RuntimeException("Unknown CSV header: {$header}"); }
        }
        if (count($headers) !== count(array_unique($headers))) { throw new RuntimeException('CSV headers must be unique.'); }
    }

    /** @param array<string,string> $row @return array<string,mixed> */
    private function validateRow(array $row, int $line): array
    {
        $errors = [];
        $originalEmail = trim((string) ($row['email'] ?? ''));
        $email = EmailNormalizer::normalize($originalEmail);
        if ($email === null) { $errors[] = 'invalid_email'; }
        try { $percentage = Percentage::percentToBasisPoints((string) ($row['discount_percentage'] ?? '')); }
        catch (\Throwable) { $percentage = 0; $errors[] = 'invalid_percentage'; }
        $reason = trim((string) ($row['reason'] ?? '')) ?: 'Individual approval';
        if (mb_strlen($reason) > 255) { $errors[] = 'reason_too_long'; }
        $notes = trim((string) ($row['notes'] ?? '')) ?: null;
        if ($notes !== null && mb_strlen($notes) > 5000) { $errors[] = 'notes_too_long'; }
        $validFrom = $this->parseDate($row['valid_from'] ?? '', false, $errors, 'invalid_valid_from');
        $validUntil = $this->parseDate($row['valid_until'] ?? '', true, $errors, 'invalid_valid_until');
        if ($validFrom && $validUntil && $validUntil->lt($validFrom)) { $errors[] = 'invalid_validity_range'; }
        return [
            'status' => $errors === [] ? 'accepted' : 'rejected',
            'line' => $line,
            'original_email' => $originalEmail,
            'normalized_email' => $email,
            'percentage_basis_points' => $percentage,
            'reason' => $reason,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'notes' => $notes,
            'errors' => $errors,
        ];
    }

    /** @param list<string> $errors */
    private function parseDate(string $value, bool $endOfDay, array &$errors, string $error): ?Carbon
    {
        $value = trim($value);
        if ($value === '') { return null; }
        $date = Carbon::createFromFormat('!Y-m-d', $value);
        $lastErrors = Carbon::getLastErrors();
        if ($date === false || (is_array($lastErrors) && (($lastErrors['warning_count'] ?? 0) > 0 || ($lastErrors['error_count'] ?? 0) > 0))) {
            $errors[] = $error;
            return null;
        }
        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }
}
