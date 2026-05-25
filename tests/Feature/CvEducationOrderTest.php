<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvEducationOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_education_order_can_be_reversed_and_persisted(): void
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

        $first = $profile->education()->create([
            'institution' => 'First University',
            'degree' => 'Bachelor',
            'end_date' => '2020-06-01',
            'sort_order' => 1,
        ]);

        $second = $profile->education()->create([
            'institution' => 'Second University',
            'degree' => 'Master',
            'end_date' => '2022-06-01',
            'sort_order' => 2,
        ]);

        $this
            ->actingAs($user)
            ->patch(route('cv.education.reverse-order', $profile))
            ->assertRedirect(route('cv.show', $profile));

        $this->assertSame(2, $first->fresh()->sort_order);
        $this->assertSame(1, $second->fresh()->sort_order);
    }
}
