<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user_roles(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('cliente');
        Role::findOrCreate('jefe_cuenta');
        Role::findOrCreate('usuario_subordinado');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $owner = User::factory()->create();
        $owner->assignRole('jefe_cuenta');
        $account = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $account), [
                'roles' => ['usuario_subordinado'],
                'account_owner_id' => $owner->id,
            ])
            ->assertRedirect(route('admin.users.edit', $account));

        $this->assertTrue($account->fresh()->hasRole('usuario_subordinado'));
        $this->assertSame($owner->id, $account->fresh()->account_owner_id);
    }

    public function test_admin_cannot_remove_last_admin_role(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('cliente');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $admin), [
                'roles' => ['cliente'],
            ])
            ->assertSessionHasErrors('roles');

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
