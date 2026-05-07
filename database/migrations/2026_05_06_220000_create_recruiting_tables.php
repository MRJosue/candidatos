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
        Schema::create('recruiter_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('website_url')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        Schema::create('talents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->string('headline')->nullable();
            $table->string('target_position')->nullable();
            $table->string('seniority')->nullable();
            $table->string('source')->nullable();
            $table->string('status')->default('active');
            $table->string('availability')->nullable();
            $table->unsignedInteger('salary_expectation_min')->nullable();
            $table->unsignedInteger('salary_expectation_max')->nullable();
            $table->char('currency', 3)->default('MXN');
            $table->json('technical_stack')->nullable();
            $table->json('languages')->nullable();
            $table->json('links')->nullable();
            $table->text('technical_summary')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();

            $table->index(['recruiter_id', 'status']);
            $table->index(['recruiter_id', 'last_name', 'first_name']);
        });

        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('client_company')->nullable();
            $table->string('location')->nullable();
            $table->string('work_mode')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('seniority')->nullable();
            $table->string('status')->default('open');
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->char('currency', 3)->default('MXN');
            $table->json('technical_stack')->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['recruiter_id', 'status']);
            $table->index(['recruiter_id', 'title']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('talent_id')->constrained('talents')->cascadeOnDelete();
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('applied');
            $table->string('stage')->default('screening');
            $table->unsignedTinyInteger('match_score')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['talent_id', 'vacancy_id']);
            $table->index(['recruiter_id', 'status']);
            $table->index(['vacancy_id', 'stage']);
        });

        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->foreignId('talent_id')
                ->nullable()
                ->after('user_id')
                ->constrained('talents')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cv_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('talent_id');
        });

        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('vacancies');
        Schema::dropIfExists('talents');
        Schema::dropIfExists('recruiter_profiles');
    }
};
