<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_admin_can_update_user_name_email_and_password(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('cliente');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $account = User::factory()->create([
            'name' => 'Nombre anterior',
            'email' => 'anterior@example.com',
        ]);
        $account->assignRole('cliente');

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $account))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $account), [
                'name' => 'Nombre actualizado',
                'email' => 'actualizado@example.com',
                'password' => 'nueva-password',
                'password_confirmation' => 'nueva-password',
                'roles' => ['cliente'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.users.edit', $account));

        $account->refresh();

        $this->assertSame('Nombre actualizado', $account->name);
        $this->assertSame('actualizado@example.com', $account->email);
        $this->assertTrue(Hash::check('nueva-password', $account->password));
        $this->assertTrue($account->first_login);
        $this->assertTrue($account->hasRole('cliente'));
    }

    public function test_admin_can_open_create_user_page_from_users_index(): void
    {
        Role::findOrCreate('admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Crear usuario')
            ->assertSee(route('admin.users.create'), false);

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Crear usuario')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_admin_can_create_user_with_roles_and_account_owner(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('jefe_cuenta');
        Role::findOrCreate('usuario_subordinado');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $owner = User::factory()->create();
        $owner->assignRole('jefe_cuenta');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Nueva Reclutadora',
                'email' => 'nueva.reclutadora@example.com',
                'password' => 'password-seguro',
                'password_confirmation' => 'password-seguro',
                'roles' => ['usuario_subordinado'],
                'account_owner_id' => $owner->id,
            ])
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'nueva.reclutadora@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password-seguro', $user->password));
        $this->assertTrue($user->first_login);
        $this->assertTrue($user->hasRole('usuario_subordinado'));
        $this->assertSame($owner->id, $user->account_owner_id);
    }

    public function test_admin_can_impersonate_user_from_users_index(): void
    {
        Role::findOrCreate('admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $account = User::factory()->create([
            'first_login' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(route('admin.users.impersonate', $account), false)
            ->assertSee('Iniciar sesion');

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $account))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($account);
    }

    public function test_admin_impersonating_first_login_user_goes_to_profile(): void
    {
        Role::findOrCreate('admin');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $account = User::factory()->create([
            'first_login' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $account))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'first-login');

        $this->assertAuthenticatedAs($account);
    }

    public function test_admin_can_assign_atc_account_owner_to_subordinate(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('jefe_atc');
        Role::findOrCreate('usuario_subordinado');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $owner = User::factory()->create();
        $owner->assignRole('jefe_atc');
        $account = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $account), [
                'roles' => ['usuario_subordinado'],
                'account_owner_id' => $owner->id,
            ])
            ->assertRedirect(route('admin.users.edit', $account));

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
