<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_job_can_be_unchecked_when_updating_experience(): void
    {
        $user = User::factory()->create();

        $profile = CvProfile::create([
            'user_id' => $user->id,
            'title' => 'Software developer',
            'full_name' => 'Josue Daniel Cardona',
            'email' => 'josue@example.com',
            'headline' => 'Backend developer',
            'summary' => 'Builds reliable Laravel applications.',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $experience = $profile->experiences()->create([
            'company' => 'Acme',
            'position' => 'Developer',
            'start_date' => '2023-01-01',
            'is_current' => true,
        ]);

        $this
            ->actingAs($user)
            ->put(route('experiences.update', $experience), [
                'company' => 'Acme',
                'position' => 'Developer',
                'location' => 'Remote',
                'start_date' => '2023-01-01',
                'end_date' => '2024-01-01',
                'description' => 'Built Laravel applications.',
                'sort_order' => 0,
            ])
            ->assertRedirect(route('cv.show', $profile));

        $this->assertFalse($experience->fresh()->is_current);
    }
}
