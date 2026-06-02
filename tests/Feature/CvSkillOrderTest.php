<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvSkillOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_skills_can_be_reordered_within_the_same_column(): void
    {
        $user = User::factory()->create();
        $profile = $this->profileFor($user);

        $first = $profile->skills()->create([
            'name' => 'Laravel',
            'type' => 'skill',
            'sort_order' => 1,
        ]);

        $second = $profile->skills()->create([
            'name' => 'Vue',
            'type' => 'skill',
            'sort_order' => 2,
        ]);

        $this
            ->actingAs($user)
            ->patchJson(route('cv.skills.reorder', $profile), [
                'columns' => [
                    'software' => [],
                    'skills' => [$second->id, $first->id],
                    'languages' => [],
                    'certifications' => [],
                ],
            ])
            ->assertOk();

        $this->assertSame(2, $first->fresh()->sort_order);
        $this->assertSame(1, $second->fresh()->sort_order);
        $this->assertSame('skill', $first->fresh()->type);
        $this->assertSame('skill', $second->fresh()->type);
    }

    public function test_skill_can_be_moved_between_columns(): void
    {
        $user = User::factory()->create();
        $profile = $this->profileFor($user);

        $jira = $profile->skills()->create([
            'name' => 'Jira',
            'type' => 'software',
            'sort_order' => 1,
        ]);

        $laravel = $profile->skills()->create([
            'name' => 'Laravel',
            'type' => 'skill',
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($user)
            ->patchJson(route('cv.skills.reorder', $profile), [
                'columns' => [
                    'software' => [],
                    'skills' => [$laravel->id, $jira->id],
                    'languages' => [],
                    'certifications' => [],
                ],
            ])
            ->assertOk();

        $this->assertSame('skill', $jira->fresh()->type);
        $this->assertSame(2, $jira->fresh()->sort_order);
        $this->assertSame('skill', $laravel->fresh()->type);
        $this->assertSame(1, $laravel->fresh()->sort_order);
    }

    public function test_skill_reorder_rejects_duplicate_items(): void
    {
        $user = User::factory()->create();
        $profile = $this->profileFor($user);

        $skill = $profile->skills()->create([
            'name' => 'Laravel',
            'type' => 'skill',
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($user)
            ->patchJson(route('cv.skills.reorder', $profile), [
                'columns' => [
                    'software' => [$skill->id],
                    'skills' => [$skill->id],
                    'languages' => [],
                    'certifications' => [],
                ],
            ])
            ->assertStatus(422);
    }

    private function profileFor(User $user): CvProfile
    {
        return CvProfile::create([
            'user_id' => $user->id,
            'title' => 'Software developer',
            'full_name' => 'Josue Daniel Cardona',
            'email' => 'josue@example.com',
            'headline' => 'Backend developer',
            'summary' => 'Builds reliable Laravel applications.',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);
    }
}
