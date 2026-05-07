<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('cv_profiles')
            ->select('talent_id')
            ->whereNotNull('talent_id')
            ->groupBy('talent_id')
            ->havingRaw('count(*) > 1')
            ->pluck('talent_id')
            ->each(function (int $talentId): void {
                $cvIds = DB::table('cv_profiles')
                    ->where('talent_id', $talentId)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->pluck('id');

                DB::table('cv_profiles')
                    ->whereIn('id', $cvIds->skip(1)->all())
                    ->update(['talent_id' => null]);
            });

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->unique('talent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropUnique(['talent_id']);
        });
    }
};
