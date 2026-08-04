<?php

declare(strict_types=1);

use ConferenceDiscountEligibility\Database\SchemaDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        SchemaDefinition::up();
    }

    public function down(): void
    {
        if (Schema::hasTable('conference_discount_coupons')
            && Schema::hasColumn('conference_discount_coupons', 'code_encrypted')) {
            Schema::table('conference_discount_coupons', static function (Blueprint $table): void {
                $table->dropColumn('code_encrypted');
            });
        }
    }
};
