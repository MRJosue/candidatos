<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->json('section_order')->nullable()->after('soft_skills_section_title');
        });
    }

    public function down(): void
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropColumn('section_order');
        });
    }
};
