<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cv_usage_plans')->updateOrInsert(
            ['slug' => 'inicial-50'],
            [
                'name' => 'Inicial 50',
                'monthly_quota' => 50,
                'price_before_tax_cents' => 125000,
                'price_with_tax_cents' => 145000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('cv_usage_plans')->updateOrInsert(
            ['slug' => 'inicial-100'],
            [
                'name' => 'Inicial 100',
                'monthly_quota' => 100,
                'price_before_tax_cents' => 195000,
                'price_with_tax_cents' => 226200,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('cv_usage_plans')
            ->whereIn('slug', ['inicial-50', 'inicial-100'])
            ->delete();
    }
};
