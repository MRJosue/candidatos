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

    public function test_cv_show_removes_preview_modal_and_keeps_download(): void
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
            ->assertDontSee('data-cv-preview-open', false)
            ->assertDontSee('id="cv-preview-dialog"', false)
            ->assertDontSee('data-cv-preview-frame', false)
            ->assertDontSee('id="cv-preview-html"', false)
            ->assertDontSee('Vista previa')
            ->assertSee('Descargar PDF')
            ->assertSee('action="'.route('cv.download', $profile).'"', false)
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
            'awards' => 'Oracle Cloud Platform Application Integration 2025 Certified Professional (OIC)',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $profile->skills()->create([
            'name' => 'Jira',
            'type' => 'software',
        ]);
        $profile->skills()->create([
            'name' => 'React',
            'type' => 'skill',
        ]);
        $profile->skills()->create([
            'name' => 'Ingles',
            'type' => 'language',
        ]);
        $profile->experiences()->create([
            'company' => 'ACT Digital',
            'position' => 'Backend Developer',
            'start_date' => '2024-01-01',
            'is_current' => true,
            'description' => "Builds APIs.\nConsultor | ORACLE Cliente: Alsea | 2020 - 2022\nManaged OAC.",
            'tools_used' => 'Laravel, MySQL',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('cv.preview', $profile));

        $response
            ->assertOk()
            ->assertSee('template-act', false)
            ->assertSee('ACT Digital', false)
            ->assertDontSee('abdiel@example.com')
            ->assertSee('Resumen profesional')
            ->assertSee('Periodo')
            ->assertSee('Puesto')
            ->assertSee('Funciones')
            ->assertSee('Herramientas Utilizadas')
            ->assertSee('ORACLE Cliente: Alsea')
            ->assertSee('class="act-entry-company"', false)
            ->assertSee('background: #eeeeee', false)
            ->assertSee('font-family: Arial, DejaVu Sans, sans-serif !important', false)
            ->assertSee('font-size: 10px !important', false)
            ->assertSee('.pdf-act-digital .template-act .act-name', false)
            ->assertSee('font-size: 20px !important', false)
            ->assertSee('.pdf-act-digital .template-act .act-role', false)
            ->assertSee('font-size: 12px !important', false)
            ->assertSee('.pdf-act-digital .template-act .act-section-title', false)
            ->assertSee('font-size: 15px !important', false)
            ->assertSee('.pdf-act-digital .template-act .act-entry-company', false)
            ->assertSee('font-size: 14px !important', false)
            ->assertSee('Periodo:</span> 2020 - 2022', false)
            ->assertDontSee('Consultor | ORACLE Cliente: Alsea | 2020 - 2022')
            ->assertSee('https://actdigital.com/es')
            ->assertSee('Habilidades Técnicas y Certificaciones')
            ->assertSee('Software')
            ->assertSee('Jira')
            ->assertSee('Idiomas')
            ->assertSee('Ingles')
            ->assertSee('React')
            ->assertDontSee('<li>React</li>', false)
            ->assertDontSee('<li>Jira</li>', false)
            ->assertSee('Certificaciones')
            ->assertSee('Oracle Cloud Platform Application Integration 2025 Certified Professional');
    }

    public function test_act_digital_english_template_uses_company_gray_band(): void
    {
        $user = User::factory()->create();

        $template = CvTemplate::create([
            'name' => 'ACT Digital',
            'slug' => 'act-digital',
            'description' => 'Corporate format inspired by ACT Digital.',
            'is_premium' => false,
            'price_cents' => 0,
            'currency' => 'MXN',
            'is_active' => true,
        ]);

        $profile = CvProfile::create([
            'user_id' => $user->id,
            'cv_template_id' => $template->id,
            'title' => 'ACT CV EN',
            'full_name' => 'Alex Rivera',
            'email' => 'alex@example.com',
            'headline' => 'Integration Consultant',
            'summary' => 'Builds enterprise integrations.',
            'language' => 'en',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $profile->experiences()->create([
            'company' => 'Oracle Client: Kosmos / Penoles',
            'position' => 'Integration Consultant',
            'start_date' => '2024-03-01',
            'end_date' => '2026-02-01',
            'description' => 'Developed integrations.',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('cv.preview', $profile));

        $response
            ->assertOk()
            ->assertSee('Experience')
            ->assertSee('class="act-entry-company"', false)
            ->assertSee('Oracle Client: Kosmos / Penoles')
            ->assertSee('Period:</span> 03/2024 - 02/2026', false);
    }
}
