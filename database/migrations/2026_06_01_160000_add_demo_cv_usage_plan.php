<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cv_usage_plans')->updateOrInsert(
            ['slug' => 'demo'],
            [
                'name' => 'Demo',
                'monthly_quota' => 50,
                'price_before_tax_cents' => 0,
                'price_with_tax_cents' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('cv_usage_plans')->where('slug', 'demo')->delete();
    }
};
