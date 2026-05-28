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
}
