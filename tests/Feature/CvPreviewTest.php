<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_cv_show_embeds_preview_html_without_auth_iframe_navigation(): void
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

        $skill = $profile->skills()->create([
            'name' => 'Laravel',
            'type' => 'skill',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('cv.show', $profile));

        $response
            ->assertOk()
            ->assertSee('data-cv-preview-open', false)
            ->assertSee('id="cv-preview-dialog"', false)
            ->assertSee('data-cv-preview-frame', false)
            ->assertSee('id="cv-preview-html"', false)
            ->assertSee('Josue Daniel Cardona')
            ->assertSee('action="'.route('skills.destroy', $skill).'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('value="DELETE"', false)
            ->assertDontSee('src="'.route('cv.preview', $profile).'"', false);
    }
}
