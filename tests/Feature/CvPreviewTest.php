<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\CvTemplate;
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

    public function test_cv_show_places_edit_link_inside_profile_summary_card(): void
    {
        $user = User::factory()->create();

        $profile = CvProfile::create([
            'user_id' => $user->id,
            'title' => 'CV Andrea Lopez',
            'full_name' => 'Andrea Lopez',
            'email' => 'andrea@example.com',
            'headline' => 'Desarrolladora backend PHP',
            'summary' => 'Experiencia en APIs REST y sistemas administrativos.',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('cv.show', $profile));

        $html = $response->getContent();
        $summaryCardStart = strpos($html, '<section class="bg-white p-6 rounded shadow-sm">');
        $summaryCardEnd = strpos($html, '</section>', $summaryCardStart);
        $summaryCardHtml = substr($html, $summaryCardStart, $summaryCardEnd - $summaryCardStart);
        $headerHtml = substr($html, 0, $summaryCardStart);
        $editHref = 'href="'.route('cv.edit', $profile).'"';

        $response->assertOk();
        $this->assertStringContainsString($editHref, $summaryCardHtml);
        $this->assertStringNotContainsString($editHref, $headerHtml);
    }

    public function test_act_digital_template_renders_for_preview(): void
    {
        $user = User::factory()->create();

        $template = CvTemplate::create([
            'name' => 'ACT Digital',
            'slug' => 'act-digital',
            'description' => 'Formato corporativo inspirado en ACT Digital.',
            'is_premium' => false,
            'price_cents' => 0,
            'currency' => 'MXN',
            'is_active' => true,
        ]);

        $profile = CvProfile::create([
            'user_id' => $user->id,
            'cv_template_id' => $template->id,
            'title' => 'ACT CV',
            'full_name' => 'Abdiel Salas Perez',
            'email' => 'abdiel@example.com',
            'headline' => 'Developer',
            'summary' => 'Full Stack Developer with enterprise web application experience.',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $profile->skills()->create([
            'name' => 'React',
            'type' => 'skill',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('cv.preview', $profile));

        $response
            ->assertOk()
            ->assertSee('template-act', false)
            ->assertSee('ACT Digital', false)
            ->assertDontSee('abdiel@example.com')
            ->assertSee('Professional Summary')
            ->assertSee('Habilidades Técnicas y Certificaciones');
    }
}
