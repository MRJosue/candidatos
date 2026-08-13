<?php

namespace Tests\Feature;

use App\Models\CvUsageEvent;
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

    public function test_admin_can_reset_account_usage_period(): void
    {
        $this->travelTo('2026-05-15 10:30:00');

        Role::findOrCreate('admin');
        Role::findOrCreate('jefe_cuenta');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $account = User::factory()->create();
        $account->assignRole('jefe_cuenta');
        $subscription = app(CvUsageService::class)->subscriptionFor($account);

        $subscription->update([
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.usage-subscriptions.reset-period', $account))
            ->assertRedirect(route('admin.usage-subscriptions.edit', $account));

        $subscription = $subscription->refresh();

        $this->assertSame('2026-05-15 10:30:00', $subscription->current_period_starts_at->toDateTimeString());
        $this->assertSame('2026-06-15 10:30:00', $subscription->current_period_ends_at->toDateTimeString());
    }

    public function test_edit_page_shows_subordinate_usage_and_total_consumption(): void
    {
        $this->travelTo('2026-05-15 10:30:00');

        Role::findOrCreate('admin');
        Role::findOrCreate('jefe_cuenta');
        Role::findOrCreate('usuario_subordinado');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $account = User::factory()->create(['name' => 'Cuenta Principal']);
        $account->assignRole('jefe_cuenta');
        $firstSubordinate = User::factory()->create([
            'name' => 'Ana Subordinada',
            'email' => 'ana.subordinada@example.com',
            'account_owner_id' => $account->id,
        ]);
        $firstSubordinate->assignRole('usuario_subordinado');
        $secondSubordinate = User::factory()->create([
            'name' => 'Beto Subordinado',
            'email' => 'beto.subordinado@example.com',
            'account_owner_id' => $account->id,
        ]);
        $secondSubordinate->assignRole('usuario_subordinado');

        $subscription = app(CvUsageService::class)->subscriptionFor($account);
        $subscription->update([
            'current_period_starts_at' => '2026-05-01 00:00:00',
            'current_period_ends_at' => '2026-06-01 00:00:00',
        ]);

        CvUsageEvent::create([
            'user_id' => $firstSubordinate->id,
            'cv_usage_subscription_id' => null,
            'type' => CvUsageEvent::TYPE_IMPORT_AI,
            'quantity' => 3,
            'occurred_at' => '2026-05-10 12:00:00',
        ]);
        CvUsageEvent::create([
            'user_id' => $secondSubordinate->id,
            'cv_usage_subscription_id' => $subscription->id,
            'type' => CvUsageEvent::TYPE_TRANSLATION_AI,
            'quantity' => 2,
            'occurred_at' => '2026-05-11 12:00:00',
        ]);
        CvUsageEvent::create([
            'user_id' => $firstSubordinate->id,
            'cv_usage_subscription_id' => $subscription->id,
            'type' => CvUsageEvent::TYPE_IMPORT_AI,
            'quantity' => 10,
            'occurred_at' => '2026-04-30 12:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.usage-subscriptions.edit', $account))
            ->assertOk()
            ->assertSee('5 / 600')
            ->assertSee('Ana Subordinada')
            ->assertSee('ana.subordinada@example.com')
            ->assertSee('Beto Subordinado')
            ->assertSee('beto.subordinado@example.com')
            ->assertSeeInOrder(['Ana Subordinada', '3', 'Beto Subordinado', '2']);
    }
}
