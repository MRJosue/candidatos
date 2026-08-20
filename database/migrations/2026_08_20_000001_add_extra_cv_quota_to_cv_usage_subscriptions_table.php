<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cv_usage_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('extra_cv_quota')->default(0)->after('cv_usage_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('cv_usage_subscriptions', function (Blueprint $table) {
            $table->dropColumn('extra_cv_quota');
        });
    }
};
