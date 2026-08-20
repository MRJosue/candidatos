<?php

namespace Tests\Feature;

use App\Models\CvUsageEvent;
use App\Models\CvUsagePlan;
use App\Models\User;
use App\Services\CvUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CvUsageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_usage_plan_is_available_with_50_cv_limit(): void
    {
        $plan = CvUsagePlan::where('slug', 'demo')->firstOrFail();

        $this->assertSame('Demo', $plan->name);
        $this->assertSame(50, $plan->monthly_quota);
        $this->assertSame(0, $plan->price_before_tax_cents);
        $this->assertSame(0, $plan->price_with_tax_cents);
        $this->assertTrue($plan->is_active);
    }

    public function test_initial_usage_plans_are_available_for_low_volume_accounts(): void
    {
        $initial50 = CvUsagePlan::where('slug', 'inicial-50')->firstOrFail();
        $initial100 = CvUsagePlan::where('slug', 'inicial-100')->firstOrFail();

        $this->assertSame('Inicial 50', $initial50->name);
        $this->assertSame(50, $initial50->monthly_quota);
        $this->assertSame(125000, $initial50->price_before_tax_cents);
        $this->assertSame(145000, $initial50->price_with_tax_cents);
        $this->assertTrue($initial50->is_active);

        $this->assertSame('Inicial 100', $initial100->name);
        $this->assertSame(100, $initial100->monthly_quota);
        $this->assertSame(195000, $initial100->price_before_tax_cents);
        $this->assertSame(226200, $initial100->price_with_tax_cents);
        $this->assertTrue($initial100->is_active);
    }

    public function test_it_creates_default_usage_subscription_and_tracks_consumption(): void
    {
        $user = User::factory()->create();
        $service = app(CvUsageService::class);

        $summary = $service->summary($user);

        $this->assertSame('Basico', $summary['plan']->name);
        $this->assertSame(600, $summary['quota']);
        $this->assertSame(0, $summary['used']);
        $this->assertSame(600, $summary['remaining']);

        $service->record($user, CvUsageEvent::TYPE_IMPORT_AI);
        $service->record($user, CvUsageEvent::TYPE_TRANSLATION_AI);

        $summary = $service->summary($user);

        $this->assertSame(2, $summary['used']);
        $this->assertSame(598, $summary['remaining']);
    }

    public function test_extra_cv_quota_extends_subscription_limit_for_the_account(): void
    {
        $user = User::factory()->create();
        $service = app(CvUsageService::class);
        $subscription = $service->subscriptionFor($user);

        $subscription->update(['extra_cv_quota' => 2]);

        $service->record($user, CvUsageEvent::TYPE_IMPORT_AI, quantity: 601);

        $summary = $service->summary($user);

        $this->assertSame(600, $summary['baseQuota']);
        $this->assertSame(2, $summary['extraQuota']);
        $this->assertSame(602, $summary['quota']);
        $this->assertSame(1, $summary['remaining']);
    }

    public function test_subordinates_consume_account_owner_plan_quota(): void
    {
        Role::findOrCreate('jefe_cuenta');
        Role::findOrCreate('usuario_subordinado');

        $owner = User::factory()->create();
        $owner->assignRole('jefe_cuenta');
        $firstSubordinate = User::factory()->create(['account_owner_id' => $owner->id]);
        $firstSubordinate->assignRole('usuario_subordinado');
        $secondSubordinate = User::factory()->create(['account_owner_id' => $owner->id]);
        $secondSubordinate->assignRole('usuario_subordinado');

        $plan = CvUsagePlan::create([
            'name' => 'Demo limitado',
            'slug' => 'demo-limitado',
            'monthly_quota' => 2,
            'price_before_tax_cents' => 10000,
            'price_with_tax_cents' => 11600,
            'is_active' => true,
        ]);

        $owner->cvUsageSubscription()->create([
            'cv_usage_plan_id' => $plan->id,
            'current_period_starts_at' => now()->startOfMonth(),
            'current_period_ends_at' => now()->startOfMonth()->addMonth(),
            'status' => 'active',
        ]);

        $service = app(CvUsageService::class);

        $service->record($firstSubordinate, CvUsageEvent::TYPE_IMPORT_AI);
        $service->record($secondSubordinate, CvUsageEvent::TYPE_TRANSLATION_AI);

        $summary = $service->summary($firstSubordinate);

        $this->assertSame($owner->id, $summary['accountOwner']->id);
        $this->assertSame(2, $summary['used']);
        $this->assertSame(0, $summary['remaining']);

        $this->expectException(RuntimeException::class);

        $service->record($firstSubordinate, CvUsageEvent::TYPE_IMPORT_AI);
    }
}
