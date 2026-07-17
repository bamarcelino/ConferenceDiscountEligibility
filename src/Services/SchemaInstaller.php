<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Services;

use ConferenceDiscountEligibility\Database\SchemaDefinition;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class SchemaInstaller
{
    private const LOCK_NAME = 'conference-discount-eligibility-schema-v2';

    public function install(): void
    {
        if ($this->isInstalled()) {
            return;
        }

        try {
            Cache::lock(self::LOCK_NAME, 60)->block(15, function (): void {
                if (! $this->isInstalled()) {
                    SchemaDefinition::up();
                }
            });
        } catch (LockTimeoutException $exception) {
            throw new RuntimeException(
                'Timed out while installing or upgrading Conference Discount Eligibility database tables.',
                previous: $exception,
            );
        }

        if (! $this->isInstalled()) {
            throw new RuntimeException(
                'Conference Discount Eligibility database schema was not installed or upgraded completely.',
            );
        }
    }

    public function isInstalled(): bool
    {
        foreach ([
            'conference_discount_settings',
            'conference_discount_entitlements',
            'conference_discount_domains',
            'conference_discount_payment_snapshots',
            'conference_discount_import_batches',
            'conference_discount_audit_logs',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return Schema::hasColumn('conference_discount_domains', 'identity_policy')
            && Schema::hasColumn('conference_discount_settings', 'schema_version');
    }

    public function rollback(): void
    {
        SchemaDefinition::down();
    }
}
