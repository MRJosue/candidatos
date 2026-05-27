<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->detachDuplicateSourceLanguageVariants();

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->unique(['source_cv_profile_id', 'language'], 'cv_profiles_source_language_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropUnique('cv_profiles_source_language_unique');
        });
    }

    private function detachDuplicateSourceLanguageVariants(): void
    {
        DB::table('cv_profiles')
            ->select('source_cv_profile_id', 'language')
            ->whereNotNull('source_cv_profile_id')
            ->groupBy('source_cv_profile_id', 'language')
            ->havingRaw('count(*) > 1')
            ->orderBy('source_cv_profile_id')
            ->get()
            ->each(function (object $duplicate): void {
                $cvIds = DB::table('cv_profiles')
                    ->where('source_cv_profile_id', $duplicate->source_cv_profile_id)
                    ->where('language', $duplicate->language)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->pluck('id');

                DB::table('cv_profiles')
                    ->whereIn('id', $cvIds->skip(1)->all())
                    ->update(['source_cv_profile_id' => null]);
            });
    }
};
