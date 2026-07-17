<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\AuditLogResource\Pages;

use ConferenceDiscountEligibility\Models\ConferenceDiscountAuditLog;
use ConferenceDiscountEligibility\Panel\ScheduledConference\Resources\AuditLogResource;
use ConferenceDiscountEligibility\Support\CsvSanitizer;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label(__('ConferenceDiscountEligibility::messages.export_csv'))->icon('heroicon-o-arrow-down-tray')->action(fn (): StreamedResponse => $this->export()),
        ];
    }

    private function export(): StreamedResponse
    {
        $conferenceId = (int) app()->getCurrentScheduledConference()->getKey();
        return response()->streamDownload(function () use ($conferenceId): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['created_at','action','actor_user_id','affected_user_id','auditable_type','auditable_id','origin','old_values','new_values','context']);
            ConferenceDiscountAuditLog::query()->where('scheduled_conference_id', $conferenceId)->orderBy('id')->chunkById(200, function ($records) use ($out): void {
                foreach ($records as $record) {
                    fputcsv($out, array_map([CsvSanitizer::class, 'safeCell'], [
                        $record->created_at?->toIso8601String(), $record->action, $record->actor_user_id, $record->affected_user_id,
                        $record->auditable_type, $record->auditable_id, $record->origin,
                        json_encode($record->old_values, JSON_UNESCAPED_UNICODE), json_encode($record->new_values, JSON_UNESCAPED_UNICODE), json_encode($record->context, JSON_UNESCAPED_UNICODE),
                    ]));
                }
            });
            fclose($out);
        }, 'conference-discount-audit.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
