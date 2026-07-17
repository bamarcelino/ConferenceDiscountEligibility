<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Data;

final readonly class AuthorIdentityEvidence
{
    public function __construct(
        public bool $confirmed,
        public ?string $source = null,
        public ?int $submissionId = null,
        public ?string $submissionStatus = null,
    ) {}

    public static function none(): self
    {
        return new self(false);
    }

    /** @return array<string, bool|int|string|null> */
    public function toArray(): array
    {
        return [
            'confirmed_author' => $this->confirmed,
            'author_evidence_source' => $this->source,
            'author_evidence_submission_id' => $this->submissionId,
            'author_evidence_submission_status' => $this->submissionStatus,
        ];
    }
}
