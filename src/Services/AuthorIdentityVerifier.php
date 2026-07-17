<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use App\Models\Author;
use App\Models\Enums\UserRole;
use App\Models\Submission;
use App\Models\User;
use BackedEnum;
use ConferenceDiscountEligibility\Data\AuthorIdentityEvidence;
use ConferenceDiscountEligibility\Support\AuthorEvidencePolicy;
use ConferenceDiscountEligibility\Support\EmailNormalizer;
use Illuminate\Database\Eloquent\Builder;

final class AuthorIdentityVerifier
{
    public function inspect(
        int $scheduledConferenceId,
        User $user,
        ?string $email = null,
    ): AuthorIdentityEvidence {
        $submission = $this->baseSubmissionQuery($scheduledConferenceId)
            ->where('user_id', $user->getKey())
            ->latest('id')
            ->first(['id', 'status']);

        if ($submission !== null) {
            return $this->submissionEvidence('submission_owner', $submission);
        }

        $submission = $this->baseSubmissionQuery($scheduledConferenceId)
            ->whereHas('participants', static function (Builder $query) use ($user): void {
                $query
                    ->where('user_id', $user->getKey())
                    ->whereHas('role', static function (Builder $roleQuery): void {
                        $roleQuery->where('name', UserRole::Author->value);
                    });
            })
            ->latest('id')
            ->first(['id', 'status']);

        if ($submission !== null) {
            return $this->submissionEvidence('submission_participant_author', $submission);
        }

        $normalizedEmail = EmailNormalizer::normalize($email ?? $user->email);
        if ($normalizedEmail === null) {
            return AuthorIdentityEvidence::none();
        }

        $author = Author::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$normalizedEmail])
            ->whereHas('submission', function (Builder $query) use ($scheduledConferenceId): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('scheduled_conference_id', $scheduledConferenceId)
                    ->whereIn('status', AuthorEvidencePolicy::acceptedSubmissionStatuses());
            })
            ->with(['submission' => static function (Builder $query): void {
                $query
                    ->withoutGlobalScopes()
                    ->select(['id', 'status']);
            }])
            ->latest('id')
            ->first(['id', 'submission_id', 'email']);

        if ($author === null || $author->submission === null) {
            return AuthorIdentityEvidence::none();
        }

        return new AuthorIdentityEvidence(
            confirmed: true,
            source: 'submission_author_email',
            submissionId: (int) $author->submission_id,
            authorId: (int) $author->getKey(),
            submissionStatus: $this->statusValue($author->submission->status),
        );
    }

    private function baseSubmissionQuery(int $scheduledConferenceId): Builder
    {
        return Submission::withoutGlobalScopes()
            ->where('scheduled_conference_id', $scheduledConferenceId)
            ->whereIn('status', AuthorEvidencePolicy::acceptedSubmissionStatuses());
    }

    private function submissionEvidence(
        string $source,
        Submission $submission,
    ): AuthorIdentityEvidence {
        return new AuthorIdentityEvidence(
            confirmed: true,
            source: $source,
            submissionId: (int) $submission->getKey(),
            submissionStatus: $this->statusValue($submission->status),
        );
    }

    private function statusValue(mixed $status): ?string
    {
        if ($status instanceof BackedEnum) {
            return (string) $status->value;
        }

        return is_string($status) ? $status : null;
    }
}
