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

    public function test_education_can_be_moved_and_order_is_persisted(): void
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
            ->patch(route('education.move', $second), ['direction' => 'up'])
            ->assertRedirect(route('cv.show', $profile));

        $this->assertSame(2, $first->fresh()->sort_order);
        $this->assertSame(1, $second->fresh()->sort_order);
        $this->assertSame(
            ['Second University', 'First University'],
            $profile->fresh()->education->pluck('institution')->all(),
        );
    }

    public function test_education_move_returns_json_for_async_requests(): void
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
            ->patchJson(route('education.move', $second), ['direction' => 'up'])
            ->assertOk()
            ->assertJsonPath('ordered_ids', [$second->id, $first->id])
            ->assertJsonPath('redirect_url', route('cv.show', $profile));
    }

    public function test_education_move_accepts_fetch_form_data_method_spoofing(): void
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
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->post(route('education.move', $second), [
                '_method' => 'PATCH',
                'direction' => 'up',
            ])
            ->assertOk()
            ->assertJsonPath('ordered_ids', [$second->id, $first->id]);
    }

    public function test_education_move_get_redirects_back_to_cv_without_error(): void
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

        $education = $profile->education()->create([
            'institution' => 'First University',
            'degree' => 'Bachelor',
            'end_date' => '2020-06-01',
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($user)
            ->get(route('education.move', $education))
            ->assertRedirect(route('cv.show', $profile));
    }

    public function test_education_edit_page_can_be_rendered(): void
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

        $education = $profile->education()->create([
            'institution' => 'First University',
            'degree' => 'Bachelor',
            'end_date' => '2020-06-01',
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($user)
            ->get(route('education.edit', $education))
            ->assertOk()
            ->assertSee('Editar educaci')
            ->assertSee('First University');
    }
}
