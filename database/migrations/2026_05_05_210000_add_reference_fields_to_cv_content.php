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
            $table->string('tagline')->nullable()->after('headline');
            $table->text('objective')->nullable()->after('summary');
            $table->text('awards')->nullable()->after('objective');
            $table->text('leadership_activities')->nullable()->after('awards');
            $table->text('interests')->nullable()->after('leadership_activities');
        });

        Schema::table('cv_education', function (Blueprint $table) {
            $table->string('location')->nullable()->after('institution');
            $table->string('gpa', 40)->nullable()->after('field');
            $table->string('honors')->nullable()->after('gpa');
            $table->text('thesis')->nullable()->after('honors');
            $table->text('relevant_coursework')->nullable()->after('thesis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'tagline',
                'objective',
                'awards',
                'leadership_activities',
                'interests',
            ]);
        });

        Schema::table('cv_education', function (Blueprint $table) {
            $table->dropColumn([
                'location',
                'gpa',
                'honors',
                'thesis',
                'relevant_coursework',
            ]);
        });
    }
};
