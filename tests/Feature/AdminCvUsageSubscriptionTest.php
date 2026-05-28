<?php

namespace Tests\Feature;

use App\Models\CvUsagePlan;
use App\Models\User;
use App\Services\CvUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCvUsageSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_account_usage_plan_and_period(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('jefe_cuenta');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $account = User::factory()->create();
        $account->assignRole('jefe_cuenta');
        $plan = CvUsagePlan::where('slug', 'medio-volumen')->firstOrFail();

        app(CvUsageService::class)->subscriptionFor($account);

        $this->actingAs($admin)
            ->patch(route('admin.usage-subscriptions.update', $account), [
                'cv_usage_plan_id' => $plan->id,
                'current_period_starts_at' => '2026-05-01',
                'current_period_ends_at' => '2026-06-01',
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.usage-subscriptions.edit', $account));

        $subscription = $account->refresh()->cvUsageSubscription;

        $this->assertSame($plan->id, $subscription->cv_usage_plan_id);
        $this->assertSame('2026-05-01', $subscription->current_period_starts_at->toDateString());
        $this->assertSame('2026-06-01', $subscription->current_period_ends_at->toDateString());
    }

    public function test_non_admin_cannot_manage_usage_plans(): void
    {
        $user = User::factory()->create();
        $account = User::factory()->create();
        $account->assignRole('jefe_cuenta');

        $this->actingAs($user)
            ->get(route('admin.usage-subscriptions.edit', $account))
            ->assertForbidden();
    }

    public function test_admin_cannot_assign_plan_to_non_account_owner(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('jefe_cuenta');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $account = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.usage-subscriptions.edit', $account))
            ->assertNotFound();
    }
}
