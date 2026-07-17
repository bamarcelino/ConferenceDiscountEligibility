<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('conference_discount_domains')
            && ! Schema::hasColumn('conference_discount_domains', 'identity_policy')
        ) {
            Schema::table('conference_discount_domains', function (Blueprint $table): void {
                $table->string('identity_policy', 48)->default('verified_email_only');
            });
        }

        if (
            Schema::hasTable('conference_discount_settings')
            && Schema::hasColumn('conference_discount_settings', 'schema_version')
        ) {
            DB::table('conference_discount_settings')->update(['schema_version' => 2]);
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('conference_discount_domains')
            && Schema::hasColumn('conference_discount_domains', 'identity_policy')
        ) {
            Schema::table('conference_discount_domains', function (Blueprint $table): void {
                $table->dropColumn('identity_policy');
            });
        }

        if (
            Schema::hasTable('conference_discount_settings')
            && Schema::hasColumn('conference_discount_settings', 'schema_version')
        ) {
            DB::table('conference_discount_settings')->update(['schema_version' => 1]);
        }
    }
};
