<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_usage_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('monthly_quota');
            $table->unsignedInteger('price_before_tax_cents');
            $table->unsignedInteger('price_with_tax_cents');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cv_usage_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('cv_usage_plan_id')->constrained()->restrictOnDelete();
            $table->timestamp('current_period_starts_at');
            $table->timestamp('current_period_ends_at');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('cv_usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_usage_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cv_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('occurred_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
        });

        DB::table('cv_usage_plans')->insert([
            [
                'name' => 'Basico',
                'slug' => 'basico',
                'monthly_quota' => 600,
                'price_before_tax_cents' => 415100,
                'price_with_tax_cents' => 481500,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Medio volumen',
                'slug' => 'medio-volumen',
                'monthly_quota' => 1500,
                'price_before_tax_cents' => 622700,
                'price_with_tax_cents' => 722300,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Alto volumen',
                'slug' => 'alto-volumen',
                'monthly_quota' => 3000,
                'price_before_tax_cents' => 934100,
                'price_with_tax_cents' => 1083500,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_usage_events');
        Schema::dropIfExists('cv_usage_subscriptions');
        Schema::dropIfExists('cv_usage_plans');
    }
};
