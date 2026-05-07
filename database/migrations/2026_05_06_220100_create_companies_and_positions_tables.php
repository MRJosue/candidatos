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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('website_url')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['recruiter_id', 'name']);
            $table->index(['recruiter_id', 'industry']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruiter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('department')->nullable();
            $table->string('seniority')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('work_mode')->nullable();
            $table->string('location')->nullable();
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->char('currency', 3)->default('MXN');
            $table->json('technical_stack')->nullable();
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->timestamps();

            $table->index(['recruiter_id', 'title']);
            $table->index(['company_id', 'title']);
        });

        Schema::table('vacancies', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('recruiter_id')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('position_id')
                ->nullable()
                ->after('company_id')
                ->constrained()
                ->nullOnDelete();
        });

        DB::table('vacancies')
            ->orderBy('id')
            ->get()
            ->each(function (object $vacancy): void {
                $companyId = null;

                if (filled($vacancy->client_company)) {
                    $company = DB::table('companies')
                        ->where('recruiter_id', $vacancy->recruiter_id)
                        ->where('name', $vacancy->client_company)
                        ->first();

                    $companyId = $company?->id ?? DB::table('companies')->insertGetId([
                        'recruiter_id' => $vacancy->recruiter_id,
                        'name' => $vacancy->client_company,
                        'location' => $vacancy->location,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $positionId = DB::table('positions')->insertGetId([
                    'recruiter_id' => $vacancy->recruiter_id,
                    'company_id' => $companyId,
                    'title' => $vacancy->title,
                    'seniority' => $vacancy->seniority,
                    'employment_type' => $vacancy->employment_type,
                    'work_mode' => $vacancy->work_mode,
                    'location' => $vacancy->location,
                    'salary_min' => $vacancy->salary_min,
                    'salary_max' => $vacancy->salary_max,
                    'currency' => $vacancy->currency,
                    'technical_stack' => $vacancy->technical_stack,
                    'description' => $vacancy->description,
                    'requirements' => $vacancy->requirements,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('vacancies')
                    ->where('id', $vacancy->id)
                    ->update([
                        'company_id' => $companyId,
                        'position_id' => $positionId,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('position_id');
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::dropIfExists('positions');
        Schema::dropIfExists('companies');
    }
};
