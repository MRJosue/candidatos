<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AccountHierarchyDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
