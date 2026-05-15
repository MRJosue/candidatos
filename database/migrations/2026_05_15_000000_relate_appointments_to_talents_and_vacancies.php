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
        if (Schema::hasColumn('appointments', 'service_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('service_id');
            });
        }

        if (! Schema::hasColumn('appointments', 'talent_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('talent_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('talents')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('appointments', 'vacancy_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('vacancy_id')
                    ->nullable()
                    ->after('talent_id')
                    ->constrained('vacancies')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'vacancy_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('vacancy_id');
            });
        }

        if (Schema::hasColumn('appointments', 'talent_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('talent_id');
            });
        }

        if (! Schema::hasColumn('appointments', 'service_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('service_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }
};
