<?php

namespace Tests\Feature;

use App\Models\CvProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CvDocumentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cv_edit_shows_document_import_form(): void
    {
        $user = User::factory()->create();

        $profile = CvProfile::create([
            'user_id' => $user->id,
            'title' => 'CV en proceso',
            'full_name' => 'Andrea Lopez',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $this->actingAs($user)
            ->get(route('cv.edit', $profile))
            ->assertOk()
            ->assertSee('Crear CV con IA')
            ->assertSee('Analizar cv')
            ->assertSee('Estamos procesando su solicitud.')
            ->assertSee('Secciones del CV')
            ->assertDontSee('Guardar secciones')
            ->assertSee('name="cv_document"', false)
            ->assertSee(route('cv.import-document-ai', $profile), false)
            ->assertDontSee('Parser actual');
    }

    public function test_cv_create_shows_document_import_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cv.create'))
            ->assertOk()
            ->assertSee('Crear CV con IA')
            ->assertSee('Analizar cv')
            ->assertSee('Estamos procesando su solicitud.')
            ->assertSee('Datos principales')
            ->assertSee('name="cv_document"', false)
            ->assertSee(route('cv.import-document-ai-create'), false);
    }

    public function test_user_can_update_cv_sections_directly_from_edit(): void
    {
        $user = User::factory()->create();

        $profile = CvProfile::create([
            'user_id' => $user->id,
            'title' => 'CV en proceso',
            'full_name' => 'Nombre anterior',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $this->actingAs($user)
            ->put(route('cv.sections.update', $profile), [
                'experiences_text' => implode("\n", [
                    'Senior Developer | Acme Software | 2022 - presente',
                    'Construccion de APIs y modulos administrativos.',
                ]),
                'education_text' => 'Ingenieria en Sistemas | Universidad Demo | 2017 - 2021',
                'software_text' => "Jira\nGitHub",
                'skills_text' => "Laravel\nPHP\nMySQL",
                'languages_text' => "Espanol\nIngles",
                'soft_skills_text' => "Liderazgo\nComunicacion",
            ])
            ->assertRedirect(route('cv.edit', $profile))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cv_experiences', [
            'cv_profile_id' => $profile->id,
            'position' => 'Senior Developer',
            'company' => 'Acme Software',
            'is_current' => true,
        ]);

        $this->assertDatabaseHas('cv_education', [
            'cv_profile_id' => $profile->id,
            'degree' => 'Ingenieria en Sistemas',
            'institution' => 'Universidad Demo',
        ]);

        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'Jira',
            'type' => 'software',
        ]);

        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'Laravel',
            'type' => 'skill',
        ]);

        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'Ingles',
            'type' => 'language',
        ]);
        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'Liderazgo',
            'type' => 'soft_skill',
        ]);
    }

    public function test_experience_text_splits_embedded_pipe_headers_into_new_experiences(): void
    {
        $user = User::factory()->create();

        $profile = CvProfile::create([
            'user_id' => $user->id,
            'title' => 'CV en proceso',
            'full_name' => 'Andrea Lopez',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $this->actingAs($user)
            ->put(route('cv.sections.update', $profile), [
                'experiences_text' => implode("\n", [
                    'Consultor de integraciones | ORACLE Cliente: Kosmos / Penoles | 2024 - 2026',
                    'Orquestacion de OIC.',
                    'Consultor | ORACLE Cliente: Alsea | 2020 - 2022',
                    'Capacidad y empoderamiento self-service.',
                    'Herramientas Utilizadas: Oracle Analytics Cloud (OAC), SQL',
                ]),
            ])
            ->assertRedirect(route('cv.edit', $profile))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cv_experiences', [
            'cv_profile_id' => $profile->id,
            'position' => 'Consultor de integraciones',
            'company' => 'ORACLE Cliente: Kosmos / Penoles',
            'description' => 'Orquestacion de OIC.',
        ]);

        $this->assertDatabaseHas('cv_experiences', [
            'cv_profile_id' => $profile->id,
            'position' => 'Consultor',
            'company' => 'ORACLE Cliente: Alsea',
            'description' => 'Capacidad y empoderamiento self-service.',
            'tools_used' => 'Oracle Analytics Cloud (OAC), SQL',
        ]);
    }

    public function test_user_can_update_cv_profile_and_sections_with_one_save_button(): void
    {
        $user = User::factory()->create();

        $profile = CvProfile::create([
            'user_id' => $user->id,
            'title' => 'CV en proceso',
            'full_name' => 'Nombre anterior',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $this->actingAs($user)
            ->put(route('cv.update', $profile), [
                'title' => 'CV Andrea',
                'full_name' => 'Andrea Lopez',
                'email' => 'andrea@example.com',
                'experiences_text' => implode("\n", [
                    'Tech Lead | Acme Software | 2021 - presente',
                    'Descripcion del rol',
                ]),
                'education_text' => 'Ingenieria en Sistemas | Universidad Demo | 2017 - 2021',
                'skills_text' => "Laravel\nPHP\nMySQL",
                'languages_text' => "Espanol\nIngles",
                'soft_skills_text' => "Liderazgo\nComunicacion",
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('talents.index'));

        $this->assertDatabaseHas('cv_profiles', [
            'id' => $profile->id,
            'title' => 'CV Andrea',
            'full_name' => 'Andrea Lopez',
        ]);
        $this->assertDatabaseHas('cv_experiences', [
            'cv_profile_id' => $profile->id,
            'position' => 'Tech Lead',
            'company' => 'Acme Software',
        ]);
        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'Comunicacion',
            'type' => 'soft_skill',
        ]);
    }

    public function test_user_can_analyze_document_with_ai_preview_then_apply_detected_data(): void
    {
        config()->set('services.gemini.key', 'test-key');
        config()->set('services.gemini.cv_import_model', 'gemini-2.5-flash');

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'profile' => [
                                    'full_name' => 'Andrea IA',
                                    'email' => 'andrea.ai@example.com',
                                    'phone' => '+52 555 000 1111',
                                    'location' => 'Ciudad de Mexico',
                                    'headline' => 'Laravel Engineer',
                                    'summary' => 'Especialista en productos internos.',
                                    'linkedin_url' => 'https://linkedin.com/in/andrea-ai',
                                    'portfolio_url' => 'https://andrea.dev',
                                ],
                                'experiences' => [[
                                    'position' => 'Tech Lead',
                                    'company' => 'AI Software',
                                    'period' => '2021 - presente',
                                    'description' => 'Liderazgo de APIs y equipos.',
                                ]],
                                'education' => [[
                                    'degree' => 'Ingenieria en Software',
                                    'institution' => 'Universidad IA',
                                    'period' => '2016 - 2020',
                                    'description' => '',
                                ]],
                                'software' => ['Jira', 'GitHub'],
                                'skills' => ['Laravel', 'Gemini'],
                                'languages' => ['Espanol', 'Ingles'],
                                'soft_skills' => ['Liderazgo', 'Comunicacion'],
                                'awards' => ['Curso de Arquitectura Laravel', 'Certificacion Scrum Master'],
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();
        $profile = CvProfile::create([
            'user_id' => $user->id,
            'title' => 'CV IA',
            'full_name' => 'Nombre anterior',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $document = UploadedFile::fake()->createWithContent('cv-ai.txt', 'Andrea IA Laravel Engineer andrea.ai@example.com');

        $this->actingAs($user)
            ->post(route('cv.import-document-ai', $profile), [
                'cv_document' => $document,
            ])
            ->assertRedirect(route('cv.edit', $profile))
            ->assertSessionHas("cv_document_import.{$profile->id}");

        Http::assertSent(fn ($request) => $request->hasHeader('X-goog-api-key', 'test-key')
            && str_contains($request->url(), 'models/gemini-2.5-flash:generateContent')
            && data_get($request->data(), 'generationConfig.responseMimeType') === 'application/json'
            && data_get($request->data(), 'generationConfig.responseSchema.type') === 'object'
            && ! str_contains(json_encode($request->data('generationConfig.responseSchema')), 'additionalProperties'));

        $this->assertDatabaseMissing('cv_profiles', [
            'id' => $profile->id,
            'full_name' => 'Andrea IA',
        ]);
        $this->assertDatabaseMissing('cv_experiences', [
            'cv_profile_id' => $profile->id,
            'position' => 'Tech Lead',
        ]);

        $this->actingAs($user)
            ->get(route('cv.edit', $profile))
            ->assertOk()
            ->assertSee('Previsualizacion de IA')
            ->assertSee('Andrea IA')
            ->assertSee('Tech Lead')
            ->assertSee('Jira')
            ->assertSee('Curso de Arquitectura Laravel')
            ->assertSee('Liderazgo');

        $this->actingAs($user)
            ->post(route('cv.apply-document-import', $profile), [
                'apply_profile' => '1',
                'apply_experiences' => '1',
                'apply_education' => '1',
                'apply_software' => '1',
                'apply_skills' => '1',
                'apply_languages' => '1',
                'apply_soft_skills' => '1',
            ])
            ->assertRedirect(route('cv.edit', $profile));

        $this->assertDatabaseHas('cv_profiles', [
            'id' => $profile->id,
            'full_name' => 'Andrea IA',
            'email' => 'andrea.ai@example.com',
            'location' => 'Ciudad de Mexico',
            'awards' => "Curso de Arquitectura Laravel\nCertificacion Scrum Master",
        ]);
        $this->assertDatabaseHas('cv_experiences', [
            'cv_profile_id' => $profile->id,
            'position' => 'Tech Lead',
            'company' => 'AI Software',
            'is_current' => true,
        ]);
        $this->assertDatabaseHas('cv_education', [
            'cv_profile_id' => $profile->id,
            'degree' => 'Ingenieria en Software',
            'institution' => 'Universidad IA',
        ]);
        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'Jira',
            'type' => 'software',
        ]);
        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'Gemini',
            'type' => 'skill',
        ]);
        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'Liderazgo',
            'type' => 'soft_skill',
        ]);
    }

    public function test_user_can_analyze_document_with_ai_before_creating_cv(): void
    {
        config()->set('services.gemini.key', 'test-key');
        config()->set('services.gemini.cv_import_model', 'gemini-2.5-flash');

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'profile' => [
                                    'full_name' => 'Andrea Create IA',
                                    'email' => 'andrea.create@example.com',
                                    'phone' => '+52 555 222 3333',
                                    'location' => 'Guadalajara',
                                    'headline' => 'Product Engineer',
                                    'summary' => 'Construye productos internos.',
                                    'linkedin_url' => 'https://linkedin.com/in/andrea-create',
                                    'portfolio_url' => 'https://andrea-create.dev',
                                ],
                                'experiences' => [[
                                    'position' => 'Product Engineer',
                                    'company' => 'Create AI',
                                    'period' => '2020 - presente',
                                    'description' => 'Desarrollo de plataformas.',
                                ]],
                                'education' => [[
                                    'degree' => 'Ingenieria',
                                    'institution' => 'Universidad Create',
                                    'period' => '2015 - 2019',
                                    'description' => '',
                                ]],
                                'software' => ['Figma'],
                                'skills' => ['Laravel', 'UX'],
                                'languages' => ['Espanol', 'Ingles'],
                                'soft_skills' => ['Comunicacion'],
                                'awards' => ['Curso de Product Discovery'],
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('cv.import-document-ai-create'), [
                'cv_document' => UploadedFile::fake()->createWithContent('cv-ai.txt', 'Andrea Create IA Product Engineer'),
            ])
            ->assertRedirect(route('cv.create'))
            ->assertSessionHas("cv_document_import.create.{$user->id}");

        $this->actingAs($user)
            ->get(route('cv.create'))
            ->assertOk()
            ->assertSee('Previsualizacion de IA')
            ->assertSee('Andrea Create IA')
            ->assertSee('Product Engineer')
            ->assertSee('Curso de Product Discovery')
            ->assertSee('value="Andrea Create IA"', false);

        $this->actingAs($user)
            ->post(route('cv.store'), [
                'title' => 'CV Andrea Create IA',
                'full_name' => 'Andrea Create IA',
                'email' => 'andrea.create@example.com',
                'phone' => '+52 555 222 3333',
                'location' => 'Guadalajara',
                'headline' => 'Product Engineer',
                'summary' => 'Construye productos internos.',
                'linkedin_url' => 'https://linkedin.com/in/andrea-create',
                'portfolio_url' => 'https://andrea-create.dev',
                'apply_document_import' => '1',
                'apply_profile' => '1',
                'apply_experiences' => '1',
                'apply_education' => '1',
                'apply_software' => '1',
                'apply_skills' => '1',
                'apply_languages' => '1',
                'apply_soft_skills' => '1',
            ])
            ->assertSessionHasNoErrors();

        $profile = CvProfile::where('email', 'andrea.create@example.com')->firstOrFail();

        $this->assertSame('Curso de Product Discovery', $profile->awards);
        $this->assertDatabaseHas('cv_experiences', [
            'cv_profile_id' => $profile->id,
            'position' => 'Product Engineer',
            'company' => 'Create AI',
            'is_current' => true,
        ]);
        $this->assertDatabaseHas('cv_education', [
            'cv_profile_id' => $profile->id,
            'degree' => 'Ingenieria',
            'institution' => 'Universidad Create',
        ]);
        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'Figma',
            'type' => 'software',
        ]);
        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'UX',
            'type' => 'skill',
        ]);
        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'Comunicacion',
            'type' => 'soft_skill',
        ]);
        $this->assertFalse(session()->has("cv_document_import.create.{$user->id}"));
    }

    public function test_user_can_create_cv_from_ai_without_detected_email(): void
    {
        config()->set('services.gemini.key', 'test-key');
        config()->set('services.gemini.cv_import_model', 'gemini-2.5-flash');

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'profile' => [
                                    'full_name' => 'El mas chingon TEST',
                                    'email' => '',
                                    'phone' => '',
                                    'location' => '',
                                    'headline' => 'Full Stack Developer',
                                    'summary' => 'Full Stack Developer with over 10 years of experience.',
                                    'linkedin_url' => '',
                                    'portfolio_url' => '',
                                ],
                                'experiences' => [],
                                'education' => [],
                                'skills' => ['React', 'Angular', 'TypeScript'],
                                'languages' => [],
                                'soft_skills' => [],
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('cv.import-document-ai-create'), [
                'cv_document' => UploadedFile::fake()->createWithContent('cv-ai.txt', 'El mas chingon TEST Full Stack Developer'),
            ])
            ->assertRedirect(route('cv.create'));

        $this->actingAs($user)
            ->post(route('cv.store'), [
                'title' => 'CV El mas chingon TEST',
                'full_name' => 'El mas chingon TEST',
                'email' => null,
                'headline' => 'Full Stack Developer',
                'summary' => 'Full Stack Developer with over 10 years of experience.',
                'apply_document_import' => '1',
                'apply_profile' => '1',
                'apply_skills' => '1',
            ])
            ->assertSessionHasNoErrors();

        $profile = CvProfile::where('title', 'CV El mas chingon TEST')->firstOrFail();

        $this->assertSame('', $profile->email);
        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $profile->id,
            'name' => 'TypeScript',
            'type' => 'skill',
        ]);
    }

    public function test_ai_document_import_requires_gemini_api_key(): void
    {
        config()->set('services.gemini.key', null);
        Http::fake();

        $user = User::factory()->create();
        $profile = CvProfile::create([
            'user_id' => $user->id,
            'title' => 'CV IA',
            'full_name' => 'Andrea Lopez',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $this->actingAs($user)
            ->post(route('cv.import-document-ai', $profile), [
                'cv_document' => UploadedFile::fake()->createWithContent('cv-ai.txt', 'Andrea Lopez'),
            ])
            ->assertSessionHasErrors('cv_document_ai')
            ->assertSessionMissing("cv_document_import.{$profile->id}");

        Http::assertNothingSent();
    }

    public function test_ai_document_import_handles_invalid_json_response(): void
    {
        config()->set('services.gemini.key', 'test-key');

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/*:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => 'Esto no es JSON',
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();
        $profile = CvProfile::create([
            'user_id' => $user->id,
            'title' => 'CV IA',
            'full_name' => 'Andrea Lopez',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $this->actingAs($user)
            ->post(route('cv.import-document-ai', $profile), [
                'cv_document' => UploadedFile::fake()->createWithContent('cv-ai.txt', 'Andrea Lopez'),
            ])
            ->assertSessionHasErrors('cv_document_ai')
            ->assertSessionMissing("cv_document_import.{$profile->id}");
    }

    public function test_ai_document_import_uses_fallback_model_when_primary_is_unavailable(): void
    {
        config()->set('services.gemini.key', 'test-key');
        config()->set('services.gemini.cv_import_model', 'gemini-2.5-flash');
        config()->set('services.gemini.cv_import_fallback_models', ['gemini-2.5-flash-lite']);

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent' => Http::response([
                'error' => [
                    'code' => 503,
                    'message' => 'This model is currently experiencing high demand.',
                    'status' => 'UNAVAILABLE',
                ],
            ], 503),
            'generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'profile' => [
                                    'full_name' => 'Andrea Lite',
                                    'email' => '',
                                    'phone' => '',
                                    'location' => '',
                                    'headline' => '',
                                    'summary' => '',
                                    'linkedin_url' => '',
                                    'portfolio_url' => '',
                                ],
                                'experiences' => [],
                                'education' => [],
                                'skills' => [],
                                'languages' => [],
                                'soft_skills' => [],
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();
        $profile = CvProfile::create([
            'user_id' => $user->id,
            'title' => 'CV IA',
            'full_name' => 'Nombre anterior',
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        $this->actingAs($user)
            ->post(route('cv.import-document-ai', $profile), [
                'cv_document' => UploadedFile::fake()->createWithContent('cv-ai.txt', 'Andrea Lite'),
            ])
            ->assertRedirect(route('cv.edit', $profile))
            ->assertSessionHas("cv_document_import.{$profile->id}");

        Http::assertSent(fn ($request) => str_contains($request->url(), 'models/gemini-2.5-flash:generateContent'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'models/gemini-2.5-flash-lite:generateContent'));
    }
}
