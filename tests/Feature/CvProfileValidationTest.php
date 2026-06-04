<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvProfileValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_cv_with_non_url_linkedin_value(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('cv.store'), [
                'title' => 'CV Luis Olivares',
                'full_name' => 'Luis Olivares',
                'email' => 'luis@example.com',
                'headline' => 'SAP Consultant',
                'summary' => 'Resume breve.',
                'linkedin_url' => 'linkedin.com/in/luis-olivares',
            ]);

        $response
            ->assertRedirect(route('talents.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cv_profiles', [
            'user_id' => $user->id,
            'title' => 'CV Luis Olivares',
            'linkedin_url' => 'linkedin.com/in/luis-olivares',
        ]);
    }
}
