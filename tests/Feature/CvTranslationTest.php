<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\User;
use App\Services\CvTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CvTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_translated_cv_variant(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $profile = $user->cvProfiles()->create([
            'talent_id' => $talent->id,
            'title' => 'CV Ana',
            'language' => 'es',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
            'headline' => 'Desarrolladora',
            'summary' => 'Construye aplicaciones internas.',
            'is_primary' => true,
        ]);
        $profile->experiences()->create([
            'position' => 'Desarrolladora',
            'company' => 'Acme',
            'start_date' => '2024-01-01',
            'is_current' => true,
            'description' => 'Lidera mejoras de plataforma.',
            'tools_used' => 'Laravel, MySQL',
        ]);
        $profile->skills()->create([
            'name' => 'Comunicación',
            'type' => 'soft_skill',
        ]);

        $this->app->instance(CvTranslationService::class, new class extends CvTranslationService
        {
            public function translate(CvProfile $profile, string $targetLanguage): array
            {
                return [
                    'profile' => [
                        'title' => 'Ana CV',
                        'full_name' => 'Ana Lopez',
                        'email' => 'ana@example.com',
                        'headline' => 'Developer',
                        'summary' => 'Builds internal applications.',
                        'skills_section_title' => 'Skills',
                        'soft_skills_section_title' => 'Soft skills',
                    ],
                    'experiences' => [[
                        'position' => 'Developer',
                        'company' => 'Acme',
                        'description' => 'Leads platform improvements.',
                        'tools_used' => 'Laravel, MySQL',
                    ]],
                    'education' => [],
                    'skills' => [[
                        'name' => 'Communication',
                        'type' => 'soft_skill',
                    ]],
                ];
            }
        });

        $response = $this->actingAs($user)->post(route('cv.translate', $profile), [
            'target_language' => 'en',
        ]);

        $translatedProfile = CvProfile::query()
            ->where('source_cv_profile_id', $profile->id)
            ->where('language', 'en')
            ->first();

        $this->assertNotNull($translatedProfile);
        $response->assertRedirect(route('cv.edit', $translatedProfile));
        $this->assertSame($talent->id, $translatedProfile->talent_id);
        $this->assertFalse($translatedProfile->is_primary);
        $this->assertSame('Developer', $translatedProfile->experiences()->first()->position);
        $this->assertTrue($translatedProfile->experiences()->first()->is_current);
        $this->assertSame('Laravel, MySQL', $translatedProfile->experiences()->first()->tools_used);
        $this->assertSame('Communication', $translatedProfile->skills()->first()->name);
    }
}
