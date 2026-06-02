<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AccountHierarchyDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountHierarchyDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_account_owner_and_subordinate_demo_users(): void
    {
        $this->seed(AccountHierarchyDemoSeeder::class);

        $owner = User::where('email', 'jefe.act@example.com')->firstOrFail();
        $firstSubordinate = User::where('email', 'reclutador1.act@example.com')->firstOrFail();
        $secondSubordinate = User::where('email', 'reclutador2.act@example.com')->firstOrFail();

        $this->assertTrue($owner->hasRole('jefe_cuenta'));
        $this->assertTrue($firstSubordinate->hasRole('usuario_subordinado'));
        $this->assertTrue($secondSubordinate->hasRole('usuario_subordinado'));
        $this->assertSame($owner->id, $firstSubordinate->account_owner_id);
        $this->assertSame($owner->id, $secondSubordinate->account_owner_id);
    }

    public function test_it_resets_demo_users_and_their_related_records_before_reseeding(): void
    {
        $this->seed(AccountHierarchyDemoSeeder::class);

        $owner = User::where('email', 'jefe.act@example.com')->firstOrFail();
        $subordinate = User::where('email', 'reclutador1.act@example.com')->firstOrFail();

        DB::table('sessions')->insert([
            'id' => 'demo-session-owner',
            'user_id' => $owner->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $subscriptionId = DB::table('subscriptions')->insertGetId([
            'user_id' => $subordinate->id,
            'type' => 'default',
            'stripe_id' => 'sub_demo_reseed',
            'stripe_status' => 'active',
            'stripe_price' => 'price_demo_reseed',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('subscription_items')->insert([
            'subscription_id' => $subscriptionId,
            'stripe_id' => 'si_demo_reseed',
            'stripe_product' => 'prod_demo_reseed',
            'stripe_price' => 'price_demo_reseed',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldUserIds = [$owner->id, $subordinate->id];

        $this->seed(AccountHierarchyDemoSeeder::class);

        $this->assertSame(3, User::query()->whereIn('email', [
            'jefe.act@example.com',
            'reclutador1.act@example.com',
            'reclutador2.act@example.com',
        ])->count());

        $this->assertSame(0, User::query()->whereIn('id', $oldUserIds)->count());
        $this->assertDatabaseMissing('sessions', ['id' => 'demo-session-owner']);
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscriptionId]);
        $this->assertDatabaseMissing('subscription_items', ['subscription_id' => $subscriptionId]);
    }
}
