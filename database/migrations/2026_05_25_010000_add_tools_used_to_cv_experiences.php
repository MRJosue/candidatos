<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cv_experiences', function (Blueprint $table) {
            $table->text('tools_used')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('cv_experiences', function (Blueprint $table) {
            $table->dropColumn('tools_used');
        });
    }
};
