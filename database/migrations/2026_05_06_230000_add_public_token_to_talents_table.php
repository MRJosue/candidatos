<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->uuid('public_token')->nullable()->after('user_id');
        });

        DB::table('talents')
            ->whereNull('public_token')
            ->orderBy('id')
            ->eachById(function (object $talent): void {
                DB::table('talents')
                    ->where('id', $talent->id)
                    ->update(['public_token' => (string) Str::uuid()]);
            });

        Schema::table('talents', function (Blueprint $table) {
            $table->uuid('public_token')->nullable(false)->change();
            $table->unique('public_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
