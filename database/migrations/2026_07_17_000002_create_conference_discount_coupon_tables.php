<?php

declare(strict_types=1);

use ConferenceDiscountEligibility\Database\SchemaDefinition;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SchemaDefinition::up();
    }

    public function down(): void
    {
        SchemaDefinition::down();
    }
};
