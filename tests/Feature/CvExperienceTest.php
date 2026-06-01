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

    public function test_experience_order_can_be_reversed_and_persisted(): void
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
            ->actingAs($user)
            ->patch(route('cv.experiences.reverse-order', $profile))
            ->assertRedirect(route('cv.show', $profile));

        $this->assertSame(2, $first->fresh()->sort_order);
        $this->assertSame(1, $second->fresh()->sort_order);
    }

    public function test_experience_can_be_moved_and_order_is_persisted(): void
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
            ->actingAs($user)
            ->patch(route('experiences.move', $second), ['direction' => 'up'])
            ->assertRedirect(route('cv.show', $profile));

        $this->assertSame(2, $first->fresh()->sort_order);
        $this->assertSame(1, $second->fresh()->sort_order);
        $this->assertSame(
            ['Second Company', 'First Company'],
            $profile->fresh()->experiences->pluck('company')->all(),
        );
    }

    public function test_experience_move_returns_json_for_async_requests(): void
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
            ->actingAs($user)
            ->patchJson(route('experiences.move', $second), ['direction' => 'up'])
            ->assertOk()
            ->assertJsonPath('ordered_ids', [$second->id, $first->id])
            ->assertJsonPath('redirect_url', route('cv.show', $profile));
    }

    public function test_experience_move_accepts_fetch_form_data_method_spoofing(): void
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
            ->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->post(route('experiences.move', $second), [
                '_method' => 'PATCH',
                'direction' => 'up',
            ])
            ->assertOk()
            ->assertJsonPath('ordered_ids', [$second->id, $first->id]);
    }

    public function test_experience_move_get_redirects_back_to_cv_without_error(): void
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
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($user)
            ->get(route('experiences.move', $experience))
            ->assertRedirect(route('cv.show', $profile));
    }

    public function test_experience_edit_page_can_be_rendered(): void
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
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($user)
            ->get(route('experiences.edit', $experience))
            ->assertOk()
            ->assertSee('Editar experiencia')
            ->assertSee('Acme');
    }
}
