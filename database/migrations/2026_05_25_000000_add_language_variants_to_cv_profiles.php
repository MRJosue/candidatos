<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->index('talent_id', 'cv_profiles_talent_id_variants_index');
        });

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropUnique(['talent_id']);
        });

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->string('language', 8)->default('es')->after('cv_template_id');
            $table->foreignId('source_cv_profile_id')
                ->nullable()
                ->after('language')
                ->constrained('cv_profiles')
                ->nullOnDelete();

            $table->index(['talent_id', 'language']);
            $table->index(['source_cv_profile_id', 'language']);
        });
    }

    public function down(): void
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropIndex(['talent_id', 'language']);
            $table->dropIndex(['source_cv_profile_id', 'language']);
            $table->dropConstrainedForeignId('source_cv_profile_id');
            $table->dropColumn('language');
        });

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->unique('talent_id');
            $table->dropIndex('cv_profiles_talent_id_variants_index');
        });
    }
};
