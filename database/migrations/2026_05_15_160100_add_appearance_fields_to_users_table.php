<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('application_theme_id')
                ->nullable()
                ->after('password')
                ->constrained('application_themes')
                ->nullOnDelete();
            $table->string('theme_mode', 20)->default('system')->after('application_theme_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('application_theme_id');
            $table->dropColumn('theme_mode');
        });
    }
};
