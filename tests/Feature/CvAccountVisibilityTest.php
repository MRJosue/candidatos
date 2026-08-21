<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CvAccountVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_owner_can_view_subordinate_cvs(): void
    {
        Role::findOrCreate('jefe_cuenta');
        Role::findOrCreate('usuario_subordinado');

        $owner = User::factory()->create();
        $owner->assignRole('jefe_cuenta');

        $subordinate = User::factory()->create([
            'account_owner_id' => $owner->id,
        ]);
        $subordinate->assignRole('usuario_subordinado');

        $profile = $subordinate->cvProfiles()->create([
            'title' => 'CV Subordinado',
            'full_name' => 'Talento Subordinado',
            'email' => 'talento@example.com',
        ]);

        $this->actingAs($owner)
            ->get(route('cv.index'))
            ->assertOk()
            ->assertSee('CV Subordinado')
            ->assertSee($subordinate->name);

        $this->actingAs($owner)
            ->get(route('cv.show', $profile))
            ->assertOk();
    }

    public function test_account_owner_can_update_subordinate_cv_order(): void
    {
        Role::findOrCreate('jefe_cuenta');
        Role::findOrCreate('usuario_subordinado');

        $owner = User::factory()->create();
        $owner->assignRole('jefe_cuenta');

        $subordinate = User::factory()->create([
            'account_owner_id' => $owner->id,
        ]);
        $subordinate->assignRole('usuario_subordinado');

        $profile = $subordinate->cvProfiles()->create([
            'title' => 'CV Subordinado',
            'full_name' => 'Talento Subordinado',
            'email' => 'talento@example.com',
        ]);

        $first = $profile->experiences()->create([
            'company' => 'First Company',
            'position' => 'Developer',
            'start_date' => '2021-01-01',
            'sort_order' => 1,
        ]);

        $second = $profile->experiences()->create([
            'company' => 'Second Company',
            'position' => 'Lead Developer',
            'start_date' => '2023-01-01',
            'sort_order' => 2,
        ]);

        $this
            ->actingAs($owner)
            ->patchJson(route('experiences.move', $second), ['direction' => 'up'])
            ->assertOk()
            ->assertJsonPath('ordered_ids', [$second->id, $first->id]);
    }

    public function test_subordinate_can_view_account_owner_cvs(): void
    {
        Role::findOrCreate('jefe_cuenta');
        Role::findOrCreate('usuario_subordinado');

        $owner = User::factory()->create();
        $owner->assignRole('jefe_cuenta');

        $subordinate = User::factory()->create([
            'account_owner_id' => $owner->id,
        ]);
        $subordinate->assignRole('usuario_subordinado');

        $profile = $owner->cvProfiles()->create([
            'title' => 'CV Del Jefe',
            'full_name' => 'Talento Del Jefe',
            'email' => 'talento.jefe@example.com',
        ]);

        $this->actingAs($subordinate)
            ->get(route('cv.index'))
            ->assertOk()
            ->assertSee('CV Del Jefe')
            ->assertSee($owner->name);

        $this->actingAs($subordinate)
            ->get(route('cv.show', $profile))
            ->assertOk();
    }

    public function test_account_member_can_filter_cvs_by_creator_name(): void
    {
        Role::findOrCreate('jefe_cuenta');
        Role::findOrCreate('usuario_subordinado');

        $owner = User::factory()->create(['name' => 'Jefa Cuenta']);
        $owner->assignRole('jefe_cuenta');

        $subordinate = User::factory()->create([
            'name' => 'Reclutadora Uno',
            'account_owner_id' => $owner->id,
        ]);
        $subordinate->assignRole('usuario_subordinado');

        $owner->cvProfiles()->create([
            'title' => 'CV Del Jefe',
            'full_name' => 'Talento Del Jefe',
            'email' => 'talento.jefe@example.com',
        ]);

        $subordinate->cvProfiles()->create([
            'title' => 'CV Del Subordinado',
            'full_name' => 'Talento Del Subordinado',
            'email' => 'talento.subordinado@example.com',
        ]);

        $this->actingAs($subordinate)
            ->get(route('cv.index', ['created_by' => 'Jefa']))
            ->assertOk()
            ->assertSee('CV Del Jefe')
            ->assertDontSee('CV Del Subordinado');
    }

    public function test_atc_account_owner_can_view_subordinate_cvs(): void
    {
        Role::findOrCreate('jefe_atc');
        Role::findOrCreate('usuario_subordinado');

        $owner = User::factory()->create();
        $owner->assignRole('jefe_atc');

        $subordinate = User::factory()->create([
            'account_owner_id' => $owner->id,
        ]);
        $subordinate->assignRole('usuario_subordinado');

        $profile = $subordinate->cvProfiles()->create([
            'title' => 'CV Subordinado ATC',
            'full_name' => 'Talento Subordinado ATC',
            'email' => 'talento.atc@example.com',
        ]);

        $this->actingAs($owner)
            ->get(route('cv.index'))
            ->assertOk()
            ->assertSee('CV Subordinado ATC')
            ->assertSee($subordinate->name);

        $this->actingAs($owner)
            ->get(route('cv.show', $profile))
            ->assertOk();
    }

    public function test_subordinate_cannot_view_other_users_cvs(): void
    {
        Role::findOrCreate('usuario_subordinado');

        $subordinate = User::factory()->create();
        $subordinate->assignRole('usuario_subordinado');
        $otherUser = User::factory()->create();

        $profile = $otherUser->cvProfiles()->create([
            'title' => 'CV Ajeno',
            'full_name' => 'Talento Ajeno',
            'email' => 'ajeno@example.com',
        ]);

        $this->actingAs($subordinate)
            ->get(route('cv.index'))
            ->assertOk()
            ->assertDontSee('CV Ajeno');

        $this->actingAs($subordinate)
            ->get(route('cv.show', $profile))
            ->assertForbidden();
    }

    public function test_account_members_can_update_and_delete_same_account_cvs(): void
    {
        Role::findOrCreate('jefe_cuenta');
        Role::findOrCreate('usuario_subordinado');

        $owner = User::factory()->create();
        $owner->assignRole('jefe_cuenta');

        $firstMember = User::factory()->create(['account_owner_id' => $owner->id]);
        $firstMember->assignRole('usuario_subordinado');

        $secondMember = User::factory()->create(['account_owner_id' => $owner->id]);
        $secondMember->assignRole('usuario_subordinado');

        $profile = $secondMember->cvProfiles()->create([
            'title' => 'CV Compartido',
            'full_name' => 'Talento Compartido',
            'email' => 'talento@example.com',
        ]);

        $this->actingAs($firstMember)
            ->get(route('cv.index'))
            ->assertOk()
            ->assertSee('CV Compartido')
            ->assertSee($secondMember->name);

        $this->actingAs($firstMember)
            ->put(route('cv.update', $profile), [
                'title' => 'CV Editado',
                'full_name' => 'Talento Compartido',
                'email' => 'talento@example.com',
                'headline' => 'Backend Developer',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cv.edit', $profile));

        $this->assertSame('CV Editado', $profile->refresh()->title);

        $this->actingAs($firstMember)
            ->delete(route('cv.destroy', $profile))
            ->assertRedirect(route('cv.index'));

        $this->assertNull($profile->fresh());
    }
}
