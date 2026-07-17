<?php

declare(strict_types=1);

namespace ConferenceDiscountEligibility\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SchemaDefinition
{
    public const VERSION = 3;

    public static function up(): void
    {
        self::settings();
        self::entitlements();
        self::domains();
        self::coupons();
        self::snapshots();
        self::couponRedemptions();
        self::imports();
        self::auditLogs();
        self::markSchemaVersion();
    }

    public static function down(): void
    {
        Schema::dropIfExists('conference_discount_audit_logs');
        Schema::dropIfExists('conference_discount_import_batches');
        Schema::dropIfExists('conference_discount_coupon_redemptions');
        Schema::dropIfExists('conference_discount_payment_snapshots');
        Schema::dropIfExists('conference_discount_coupons');
        Schema::dropIfExists('conference_discount_domains');
        Schema::dropIfExists('conference_discount_entitlements');
        Schema::dropIfExists('conference_discount_settings');
    }

    private static function settings(): void
    {
        if (Schema::hasTable('conference_discount_settings')) {
            Schema::table('conference_discount_settings', function (Blueprint $table): void {
                if (! Schema::hasColumn('conference_discount_settings', 'schema_version')) {
                    $table->unsignedSmallInteger('schema_version')->default(self::VERSION);
                }
                if (! Schema::hasColumn('conference_discount_settings', 'coupon_redemption_enabled')) {
                    $table->boolean('coupon_redemption_enabled')->default(true);
                }
            });

            return;
        }

        Schema::create('conference_discount_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scheduled_conference_id');
            $table->string('discount_scope', 48)->default('base_only');
            $table->json('eligible_add_on_keys')->nullable();
            $table->boolean('recalculate_unpaid_default')->default(false);
            $table->boolean('notify_on_recalculation')->default(false);
            $table->boolean('coupon_redemption_enabled')->default(true);
            $table->unsignedInteger('csv_max_bytes')->default(5242880);
            $table->unsignedSmallInteger('schema_version')->default(self::VERSION);
            $table->timestamps();
            $table->unique('scheduled_conference_id', 'cde_settings_sc_unique');
            $table->foreign('scheduled_conference_id', 'cde_settings_sc_fk')
                ->references('id')->on('scheduled_conferences')->cascadeOnDelete();
        });
    }

    private static function entitlements(): void
    {
        if (Schema::hasTable('conference_discount_entitlements')) {
            return;
        }

        Schema::create('conference_discount_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scheduled_conference_id');
            $table->string('eligibility_type', 16);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('original_email')->nullable();
            $table->string('normalized_email')->nullable();
            $table->unsignedSmallInteger('percentage_basis_points');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('active')->default(true);
            $table->string('source_type', 32)->default('manual');
            $table->string('source_reference')->nullable();
            $table->unsignedInteger('maximum_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamp('linked_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['scheduled_conference_id', 'normalized_email'], 'cde_ent_sc_email_unique');
            $table->unique(['scheduled_conference_id', 'user_id', 'eligibility_type'], 'cde_ent_sc_user_type_unique');
            $table->index('normalized_email', 'cde_ent_email_idx');
            $table->index(['scheduled_conference_id', 'eligibility_type', 'active'], 'cde_ent_lookup_idx');
            $table->index(['scheduled_conference_id', 'valid_from', 'valid_until'], 'cde_ent_validity_idx');
            $table->foreign('scheduled_conference_id', 'cde_ent_sc_fk')->references('id')->on('scheduled_conferences')->cascadeOnDelete();
            $table->foreign('user_id', 'cde_ent_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'cde_ent_created_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'cde_ent_updated_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    private static function domains(): void
    {
        if (Schema::hasTable('conference_discount_domains')) {
            if (! Schema::hasColumn('conference_discount_domains', 'identity_policy')) {
                Schema::table('conference_discount_domains', function (Blueprint $table): void {
                    $table->string('identity_policy', 48)->default('verified_email_only');
                });
            }

            return;
        }

        Schema::create('conference_discount_domains', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scheduled_conference_id');
            $table->string('original_domain');
            $table->string('normalized_domain');
            $table->unsignedSmallInteger('percentage_basis_points');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->boolean('include_subdomains')->default(false);
            $table->string('identity_policy', 48)->default('verified_email_only');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('maximum_uses')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['scheduled_conference_id', 'normalized_domain'], 'cde_domain_sc_domain_unique');
            $table->index(['scheduled_conference_id', 'active', 'valid_from', 'valid_until'], 'cde_domain_lookup_idx');
            $table->foreign('scheduled_conference_id', 'cde_domain_sc_fk')->references('id')->on('scheduled_conferences')->cascadeOnDelete();
            $table->foreign('created_by', 'cde_domain_created_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'cde_domain_updated_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    private static function coupons(): void
    {
        if (Schema::hasTable('conference_discount_coupons')) {
            return;
        }

        Schema::create('conference_discount_coupons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scheduled_conference_id');
            $table->string('name');
            $table->char('code_hash', 64);
            $table->string('code_hint', 32);
            $table->unsignedSmallInteger('percentage_basis_points');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->json('eligible_payment_types');
            $table->json('eligible_payment_fee_ids')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('maximum_uses')->nullable();
            $table->unsignedInteger('per_user_limit')->default(1);
            $table->unsignedInteger('uses_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['scheduled_conference_id', 'code_hash'], 'cde_coupon_sc_hash_unique');
            $table->index(['scheduled_conference_id', 'active', 'valid_from', 'valid_until'], 'cde_coupon_lookup_idx');
            $table->foreign('scheduled_conference_id', 'cde_coupon_sc_fk')->references('id')->on('scheduled_conferences')->cascadeOnDelete();
            $table->foreign('created_by', 'cde_coupon_created_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'cde_coupon_updated_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    private static function snapshots(): void
    {
        if (Schema::hasTable('conference_discount_payment_snapshots')) {
            if (! Schema::hasColumn('conference_discount_payment_snapshots', 'coupon_campaign_id')) {
                Schema::table('conference_discount_payment_snapshots', function (Blueprint $table): void {
                    $table->unsignedBigInteger('coupon_campaign_id')->nullable()->after('domain_rule_id');
                    $table->foreign('coupon_campaign_id', 'cde_snapshot_coupon_fk')
                        ->references('id')->on('conference_discount_coupons')->nullOnDelete();
                });
            }

            return;
        }

        Schema::create('conference_discount_payment_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scheduled_conference_id');
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('entitlement_id')->nullable();
            $table->unsignedBigInteger('domain_rule_id')->nullable();
            $table->unsignedBigInteger('coupon_campaign_id')->nullable();
            $table->unsignedBigInteger('original_base_amount_minor');
            $table->unsignedSmallInteger('discount_percentage_basis_points')->default(0);
            $table->unsignedBigInteger('base_discount_amount_minor')->default(0);
            $table->unsignedBigInteger('discount_amount_minor')->default(0);
            $table->unsignedBigInteger('final_base_amount_minor');
            $table->unsignedBigInteger('add_on_amount_minor')->default(0);
            $table->unsignedBigInteger('eligible_add_on_amount_minor')->default(0);
            $table->unsignedBigInteger('add_on_discount_amount_minor')->default(0);
            $table->unsignedBigInteger('original_total_minor');
            $table->unsignedBigInteger('final_total_minor');
            $table->char('currency', 3);
            $table->string('eligibility_type', 16)->nullable();
            $table->unsignedBigInteger('eligibility_id')->nullable();
            $table->string('eligibility_reason')->nullable();
            $table->timestamp('eligibility_snapshot_at');
            $table->string('calculation_version', 32);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('payment_id', 'cde_snapshot_payment_unique');
            $table->index(['scheduled_conference_id', 'eligibility_type'], 'cde_snapshot_sc_type_idx');
            $table->foreign('scheduled_conference_id', 'cde_snapshot_sc_fk')->references('id')->on('scheduled_conferences')->cascadeOnDelete();
            $table->foreign('payment_id', 'cde_snapshot_payment_fk')->references('id')->on('payments')->cascadeOnDelete();
            $table->foreign('user_id', 'cde_snapshot_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('entitlement_id', 'cde_snapshot_ent_fk')->references('id')->on('conference_discount_entitlements')->nullOnDelete();
            $table->foreign('domain_rule_id', 'cde_snapshot_domain_fk')->references('id')->on('conference_discount_domains')->nullOnDelete();
            $table->foreign('coupon_campaign_id', 'cde_snapshot_coupon_fk')->references('id')->on('conference_discount_coupons')->nullOnDelete();
        });
    }

    private static function couponRedemptions(): void
    {
        if (Schema::hasTable('conference_discount_coupon_redemptions')) {
            return;
        }

        Schema::create('conference_discount_coupon_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scheduled_conference_id');
            $table->unsignedBigInteger('coupon_campaign_id');
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status', 16)->default('reserved');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('payment_id', 'cde_coupon_redemption_payment_unique');
            $table->index(['coupon_campaign_id', 'status'], 'cde_coupon_redemption_campaign_status_idx');
            $table->index(['user_id', 'coupon_campaign_id', 'status'], 'cde_coupon_redemption_user_idx');
            $table->foreign('scheduled_conference_id', 'cde_coupon_redemption_sc_fk')->references('id')->on('scheduled_conferences')->cascadeOnDelete();
            $table->foreign('coupon_campaign_id', 'cde_coupon_redemption_coupon_fk')->references('id')->on('conference_discount_coupons')->cascadeOnDelete();
            $table->foreign('payment_id', 'cde_coupon_redemption_payment_fk')->references('id')->on('payments')->cascadeOnDelete();
            $table->foreign('user_id', 'cde_coupon_redemption_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    private static function imports(): void
    {
        if (Schema::hasTable('conference_discount_import_batches')) {
            return;
        }

        Schema::create('conference_discount_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scheduled_conference_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('original_filename');
            $table->char('sha256', 64);
            $table->string('duplicate_strategy', 16);
            $table->boolean('dry_run')->default(true);
            $table->string('status', 32);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('accepted_rows')->default(0);
            $table->unsignedInteger('rejected_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('ignored_rows')->default(0);
            $table->json('report')->nullable();
            $table->timestamps();

            $table->index(['scheduled_conference_id', 'created_at'], 'cde_import_sc_created_idx');
            $table->foreign('scheduled_conference_id', 'cde_import_sc_fk')->references('id')->on('scheduled_conferences')->cascadeOnDelete();
            $table->foreign('actor_user_id', 'cde_import_actor_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    private static function auditLogs(): void
    {
        if (Schema::hasTable('conference_discount_audit_logs')) {
            return;
        }

        Schema::create('conference_discount_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('scheduled_conference_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('affected_user_id')->nullable();
            $table->string('action', 96);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('context')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('origin', 64)->default('panel');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['scheduled_conference_id', 'created_at'], 'cde_audit_sc_created_idx');
            $table->index(['auditable_type', 'auditable_id'], 'cde_audit_auditable_idx');
            $table->index(['affected_user_id', 'created_at'], 'cde_audit_user_created_idx');
            $table->foreign('scheduled_conference_id', 'cde_audit_sc_fk')->references('id')->on('scheduled_conferences')->cascadeOnDelete();
            $table->foreign('actor_user_id', 'cde_audit_actor_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('affected_user_id', 'cde_audit_affected_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    private static function markSchemaVersion(): void
    {
        if (! Schema::hasTable('conference_discount_settings')
            || ! Schema::hasColumn('conference_discount_settings', 'schema_version')) {
            return;
        }

        DB::table('conference_discount_settings')
            ->where('schema_version', '<', self::VERSION)
            ->update(['schema_version' => self::VERSION]);
    }
}
