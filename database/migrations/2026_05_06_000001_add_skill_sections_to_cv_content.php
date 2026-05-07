<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->string('skills_section_title')->default('Habilidades')->after('objective');
            $table->string('soft_skills_section_title')->default('Habilidades blandas')->after('skills_section_title');
        });

        Schema::table('cv_skills', function (Blueprint $table) {
            $table->string('type', 40)->default('skill')->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cv_skills', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropColumn(['skills_section_title', 'soft_skills_section_title']);
        });
    }
};
