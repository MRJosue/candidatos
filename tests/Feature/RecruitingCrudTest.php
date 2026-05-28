<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecruitingCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_recruiter_can_manage_talents(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('talents.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
            'technical_stack' => 'PHP, Laravel, MySQL',
        ]);

        $talent = $user->talents()->first();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('talents.show', $talent));

        $this->assertSame(['PHP', 'Laravel', 'MySQL'], $talent->technical_stack);

        $this->actingAs($user)
            ->put(route('talents.update', $talent), [
                'first_name' => 'Ana',
                'last_name' => 'Lopez',
                'email' => 'ana@example.com',
                'status' => 'paused',
                'currency' => 'MXN',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('talents.show', $talent));

        $this->assertSame('paused', $talent->refresh()->status);

        $this->actingAs($user)
            ->delete(route('talents.destroy', $talent))
            ->assertRedirect(route('talents.index'));

        $this->assertNull($talent->fresh());
    }

    public function test_recruiter_can_import_talents_from_excel_preview(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('talents.import.preview'), [
                'talents_file' => $this->talentImportFile([
                    [
                        'Ana',
                        'Lopez',
                        'LinkedIn',
                        'active',
                        'Lista para entrevista.',
                        '2026-05-07',
                    ],
                ]),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('talents.import'));

        $this->assertSame(1, session('talent_import.rows.valid_count'));

        $this->actingAs($user)
            ->post(route('talents.import.store'))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('talents.index'));

        $talent = $user->talents()->first();

        $this->assertSame('Ana', $talent->first_name);
        $this->assertSame('LinkedIn', $talent->source);
        $this->assertSame('Lista para entrevista.', $talent->notes);
        $this->assertDatabaseHas('talents', [
            'recruiter_id' => $user->id,
            'first_name' => 'Ana',
            'status' => 'active',
        ]);
    }

    public function test_recruiter_can_filter_talents_by_name_and_creation_date_text(): void
    {
        $user = User::factory()->create();
        $matchingTalent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $matchingTalent->forceFill([
            'created_at' => '2026-05-20 10:00:00',
            'updated_at' => '2026-05-20 10:00:00',
        ])->save();
        $otherTalent = $user->talents()->create([
            'first_name' => 'Beto',
            'last_name' => 'Ruiz',
            'email' => 'beto@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $otherTalent->forceFill([
            'created_at' => '2026-05-21 10:00:00',
            'updated_at' => '2026-05-21 10:00:00',
        ])->save();

        $this->actingAs($user)
            ->get(route('talents.index', [
                'name' => 'Ana',
                'created_date' => '20/05/2026',
            ]))
            ->assertOk()
            ->assertSee('Buscar nombre')
            ->assertSee('dd/mm/aaaa')
            ->assertSee('Crear postulacion')
            ->assertSee('Crear nueva vacante')
            ->assertSee('Crear CV en espanol')
            ->assertDontSee('Crear CV en ingles')
            ->assertDontSee('Abrir CV')
            ->assertDontSee('Editar CV</a>', false)
            ->assertDontSee('Nueva postulacion')
            ->assertSee($matchingTalent->full_name)
            ->assertDontSee('Beto Ruiz');
    }

    public function test_talents_index_shows_newest_records_first_by_id(): void
    {
        $user = User::factory()->create();

        $oldestTalent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $middleTalent = $user->talents()->create([
            'first_name' => 'Beto',
            'last_name' => 'Ruiz',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $newestTalent = $user->talents()->create([
            'first_name' => 'Carla',
            'last_name' => 'Mendez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);

        $this->actingAs($user)
            ->get(route('talents.index'))
            ->assertOk()
            ->assertSeeInOrder([
                $newestTalent->full_name,
                $middleTalent->full_name,
                $oldestTalent->full_name,
            ]);
    }

    public function test_account_owner_can_view_subordinate_talents(): void
    {
        Role::findOrCreate('jefe_atc');
        Role::findOrCreate('usuario_subordinado');

        $owner = User::factory()->create();
        $owner->assignRole('jefe_atc');
        $subordinate = User::factory()->create([
            'account_owner_id' => $owner->id,
        ]);
        $subordinate->assignRole('usuario_subordinado');
        $otherUser = User::factory()->create();

        $subordinateTalent = $subordinate->talents()->create([
            'first_name' => 'Subordinado',
            'last_name' => 'Visible',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $otherTalent = $otherUser->talents()->create([
            'first_name' => 'Talento',
            'last_name' => 'Ajeno',
            'status' => 'active',
            'currency' => 'MXN',
        ]);

        $this->actingAs($owner)
            ->get(route('talents.index'))
            ->assertOk()
            ->assertSee($subordinateTalent->full_name)
            ->assertDontSee($otherTalent->full_name)
            ->assertDontSee(route('talents.edit', $subordinateTalent), false);

        $this->actingAs($owner)
            ->get(route('talents.show', $subordinateTalent))
            ->assertOk();
    }

    public function test_user_cannot_view_unrelated_talent(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $talent = $otherUser->talents()->create([
            'first_name' => 'Talento',
            'last_name' => 'Ajeno',
            'status' => 'active',
            'currency' => 'MXN',
        ]);

        $this->actingAs($user)
            ->get(route('talents.show', $talent))
            ->assertForbidden();
    }

    public function test_recruiter_can_download_talent_import_layout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('talents.import.layout'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->assertNotEmpty($response->streamedContent());
    }

    public function test_talent_import_page_uses_relative_import_urls(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('talents.import'));

        $response
            ->assertOk()
            ->assertSee('href="/talents/import/layout"', false)
            ->assertSee('action="/talents/import/preview"', false)
            ->assertDontSee('https://candidatos.icu', false);
    }

    public function test_recruiter_can_manage_companies(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('companies.store'), [
            'name' => 'Acme',
            'industry' => 'Software',
            'email' => 'contacto@acme.test',
            'website_url' => 'https://example.com',
            'location' => 'Mexico',
            'notes' => 'Cliente estrategico',
        ]);

        $company = $user->companies()->first();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('companies.show', $company));

        $this->assertSame('Acme', $company->name);
        $this->assertSame('Software', $company->industry);
        $this->assertSame('contacto@acme.test', $company->email);

        $this->actingAs($user)
            ->put(route('companies.update', $company), [
                'name' => 'Acme Labs',
                'industry' => 'Tecnologia',
                'email' => 'talento@acme.test',
                'website_url' => 'https://example.com',
                'location' => 'Guadalajara',
                'notes' => 'Cliente recurrente',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('companies.show', $company));

        $company->refresh();

        $this->assertSame('Acme Labs', $company->name);
        $this->assertSame('talento@acme.test', $company->email);
        $this->assertSame('Guadalajara', $company->location);

        $this->actingAs($user)
            ->delete(route('companies.destroy', $company))
            ->assertRedirect(route('companies.index'));

        $this->assertNull($company->fresh());
    }

    public function test_recruiter_can_manage_vacancies_with_company_and_position(): void
    {
        $user = User::factory()->create();
        $company = Company::create([
            'recruiter_id' => $user->id,
            'name' => 'Acme',
            'industry' => 'Software',
        ]);

        $payload = [
            'company_id' => $company->id,
            'position_title' => 'Backend Developer',
            'seniority' => 'Senior',
            'employment_type' => 'Full time',
            'work_mode' => 'Remoto',
            'location' => 'Mexico',
            'currency' => 'MXN',
            'technical_stack' => 'Laravel, Redis',
            'status' => 'open',
        ];

        $response = $this->actingAs($user)->post(route('vacancies.store'), $payload);

        $vacancy = $user->vacancies()->with(['company', 'position'])->first();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('vacancies.show', $vacancy));

        $this->assertSame('Acme', $vacancy->company->name);
        $this->assertSame('Backend Developer', $vacancy->position->title);
        $this->assertSame(['Laravel', 'Redis'], $vacancy->position->technical_stack);

        $this->actingAs($user)
            ->put(route('vacancies.update', $vacancy), [
                ...$payload,
                'position_title' => 'Lead Backend Developer',
                'status' => 'paused',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('vacancies.show', $vacancy));

        $vacancy->refresh()->load('position');

        $this->assertSame('paused', $vacancy->status);
        $this->assertSame('Lead Backend Developer', $vacancy->position->title);

        $this->actingAs($user)
            ->delete(route('vacancies.destroy', $vacancy))
            ->assertRedirect(route('vacancies.index'));

        $this->assertNull($vacancy->fresh());
    }

    public function test_recruiter_can_create_applications_from_talent_to_different_vacancies(): void
    {
        $user = User::factory()->create();
        $company = $user->companies()->create([
            'name' => 'Acme',
        ]);
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $firstVacancy = $user->vacancies()->create([
            'company_id' => $company->id,
            'title' => 'Backend Developer',
            'client_company' => $company->name,
            'status' => 'open',
            'currency' => 'MXN',
        ]);
        $secondVacancy = $user->vacancies()->create([
            'company_id' => $company->id,
            'title' => 'Frontend Developer',
            'client_company' => $company->name,
            'status' => 'open',
            'currency' => 'MXN',
        ]);

        $this->actingAs($user)
            ->post(route('talents.applications.store', $talent), [
                'vacancy_id' => $firstVacancy->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('talents.index'));

        $this->actingAs($user)
            ->post(route('talents.applications.store', $talent), [
                'vacancy_id' => $secondVacancy->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('talents.index'));

        $this->assertCount(2, $talent->applications()->get());

        $this->actingAs($user)
            ->post(route('talents.applications.store', $talent), [
                'vacancy_id' => $firstVacancy->id,
            ])
            ->assertSessionHasErrors('vacancy_id');
    }

    public function test_recruiter_can_manage_job_applications(): void
    {
        $user = User::factory()->create();
        $company = $user->companies()->create([
            'name' => 'Acme',
        ]);
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $vacancy = $user->vacancies()->create([
            'company_id' => $company->id,
            'title' => 'Backend Developer',
            'client_company' => $company->name,
            'status' => 'open',
            'currency' => 'MXN',
        ]);

        $response = $this->actingAs($user)->post(route('applications.store'), [
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'status' => 'applied',
            'stage' => 'review',
            'match_score' => 82,
            'notes' => 'Buen perfil',
        ]);

        $application = $user->jobApplications()->first();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('applications.show', $application));

        $this->assertSame($talent->id, $application->talent_id);
        $this->assertSame($vacancy->id, $application->vacancy_id);
        $this->assertSame(82, $application->match_score);

        $this->actingAs($user)
            ->put(route('applications.update', $application), [
                'talent_id' => $talent->id,
                'vacancy_id' => $vacancy->id,
                'status' => 'active',
                'stage' => 'technical_interview',
                'match_score' => 90,
                'notes' => 'Avanza a entrevista',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('applications.show', $application));

        $application->refresh();

        $this->assertSame('active', $application->status);
        $this->assertSame('technical_interview', $application->stage);
        $this->assertSame(90, $application->match_score);

        $this->actingAs($user)
            ->delete(route('applications.destroy', $application))
            ->assertRedirect(route('applications.index'));

        $this->assertNull($application->fresh());
    }

    public function test_recruiter_can_filter_and_export_job_applications(): void
    {
        $user = User::factory()->create();
        $company = $user->companies()->create(['name' => 'Acme']);
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $otherTalent = $user->talents()->create([
            'first_name' => 'Beto',
            'last_name' => 'Ruiz',
            'email' => 'beto@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $vacancy = $user->vacancies()->create([
            'company_id' => $company->id,
            'title' => 'Backend Developer',
            'client_company' => $company->name,
            'status' => 'open',
            'currency' => 'MXN',
        ]);
        $otherVacancy = $user->vacancies()->create([
            'company_id' => $company->id,
            'title' => 'Frontend Developer',
            'client_company' => $company->name,
            'status' => 'open',
            'currency' => 'MXN',
        ]);

        $user->jobApplications()->create([
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'status' => 'active',
            'stage' => 'review',
            'match_score' => 90,
            'last_activity_at' => '2026-05-07 12:50:00',
        ]);
        $user->jobApplications()->create([
            'talent_id' => $otherTalent->id,
            'vacancy_id' => $otherVacancy->id,
            'status' => 'rejected',
            'stage' => 'technical_interview',
            'match_score' => 40,
            'last_activity_at' => '2026-05-08 09:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('applications.index', ['status' => 'active', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Limpiar filtros')
            ->assertSee('Exportar Excel')
            ->assertSee('Descargar CVs seleccionados')
            ->assertSee('Seleccionar todas las postulaciones')
            ->assertSee('Ana Lopez')
            ->assertDontSee('beto@example.com');

        $response = $this->actingAs($user)
            ->get(route('applications.export', ['status' => 'active']));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $path = tempnam(sys_get_temp_dir(), 'postulaciones').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('Postulante', $sheet->getCell('A1')->getValue());
        $this->assertSame('Ana Lopez', $sheet->getCell('A2')->getValue());
        $this->assertSame('Activa', $sheet->getCell('E2')->getValue());
        $this->assertNull($sheet->getCell('A3')->getValue());

        @unlink($path);
    }

    public function test_applications_index_and_export_use_cv_email_when_talent_email_is_empty(): void
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
            'full_name' => 'Ana Lopez',
            'email' => 'ana.cv@example.com',
        ]);
        $vacancy = $user->vacancies()->create([
            'title' => 'Backend Developer',
            'status' => 'open',
            'currency' => 'MXN',
        ]);

        $user->jobApplications()->create([
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'cv_profile_id' => $profile->id,
            'status' => 'active',
            'stage' => 'review',
            'last_activity_at' => '2026-05-07 12:50:00',
        ]);

        $this->actingAs($user)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertSee('ana.cv@example.com');

        $response = $this->actingAs($user)->get(route('applications.export'));

        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'postulaciones').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame('ana.cv@example.com', $sheet->getCell('B2')->getValue());

        @unlink($path);
    }

    public function test_templates_index_creates_default_templates_when_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('templates.index'))
            ->assertOk()
            ->assertSee('ACT Digital')
            ->assertSee('Academico bullet')
            ->assertDontSee('Clasico profesional')
            ->assertDontSee('Ejecutivo premium');
    }

    public function test_recruiter_can_create_and_assign_cv_from_talent(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);

        $this->actingAs($user)
            ->post(route('cv.store'), [
                'talent_id' => $talent->id,
                'title' => 'CV Ana',
                'full_name' => 'Ana Lopez',
                'email' => 'ana@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('talents.index'));

        $this->assertSame('CV Ana', $talent->refresh()->cvProfile->title);
    }

    public function test_recruiter_can_assign_cv_to_talent_from_cv_index_modal(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $profile = $user->cvProfiles()->create([
            'title' => 'CV Ana',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);

        $this->actingAs($user)
            ->patch(route('cv.talent.update', $profile), [
                'talent_id' => $talent->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cv.index'));

        $this->assertSame($talent->id, $profile->refresh()->talent_id);
    }

    public function test_recruiter_returns_to_talents_after_updating_assigned_cv(): void
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
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);

        $this->actingAs($user)
            ->put(route('cv.update', $profile), [
                'talent_id' => $talent->id,
                'title' => 'CV Andrea',
                'full_name' => 'Andrea Lopez',
                'email' => 'andrea@example.com',
                'headline' => 'Desarrolladora backend PHP',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('talents.index'));

        $this->assertSame('CV Andrea', $profile->refresh()->title);
    }

    public function test_recruiter_can_create_cv_directly_from_talent(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);

        $this->actingAs($user)
            ->post(route('talents.cv.store', $talent))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $profile = $talent->refresh()->cvProfile;

        $this->assertNotNull($profile);
        $this->assertSame('CV Ana Lopez', $profile->title);
        $this->assertSame('Ana Lopez', $profile->full_name);
        $this->assertNull($profile->headline);
    }

    public function test_recruiter_can_create_english_cv_only_after_spanish_cv_exists(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);

        $this->actingAs($user)
            ->post(route('talents.cv.store', $talent), [
                'language' => 'en',
            ])
            ->assertSessionHasErrors('language');

        $spanishProfile = $user->cvProfiles()->create([
            'talent_id' => $talent->id,
            'title' => 'CV Ana',
            'language' => 'es',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('talents.cv.store', $talent), [
                'language' => 'en',
            ])
            ->assertSessionHasNoErrors();

        $englishProfile = $talent->refresh()->cvProfiles()
            ->where('language', 'en')
            ->first();

        $this->assertNotNull($englishProfile);
        $response->assertRedirect(route('cv.show', $englishProfile));
        $this->assertSame($spanishProfile->id, $englishProfile->source_cv_profile_id);
    }

    public function test_talent_actions_show_language_specific_cv_options(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $spanishProfile = $user->cvProfiles()->create([
            'talent_id' => $talent->id,
            'title' => 'CV Ana ES',
            'language' => 'es',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('talents.index'))
            ->assertOk()
            ->assertSee('Editar CV espanol')
            ->assertSee('Crear CV en ingles')
            ->assertSee(route('cv.show', $spanishProfile), false)
            ->assertDontSee('Abrir CV')
            ->assertDontSee('Editar CV</a>', false);

        $englishProfile = $user->cvProfiles()->create([
            'talent_id' => $talent->id,
            'title' => 'CV Ana EN',
            'language' => 'en',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('talents.index'))
            ->assertOk()
            ->assertSee('Editar CV espanol')
            ->assertSee('Editar CV en ingles')
            ->assertSee(route('cv.show', $spanishProfile), false)
            ->assertSee(route('cv.show', $englishProfile), false)
            ->assertDontSee('Crear CV en ingles');
    }

    public function test_recruiter_can_assign_multiple_cvs_to_same_talent(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $assignedProfile = $user->cvProfiles()->create([
            'talent_id' => $talent->id,
            'title' => 'CV Actual',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
            'is_primary' => true,
        ]);
        $newProfile = $user->cvProfiles()->create([
            'title' => 'CV Nuevo',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);

        $this->actingAs($user)
            ->patch(route('cv.talent.update', $newProfile), [
                'talent_id' => $talent->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('cv.index'));

        $this->assertSame($talent->id, $assignedProfile->refresh()->talent_id);
        $this->assertSame($talent->id, $newProfile->refresh()->talent_id);
        $this->assertFalse($newProfile->is_primary);
        $this->assertCount(2, $talent->refresh()->cvProfiles);
    }

    public function test_recruiter_cannot_assign_more_than_two_cvs_to_same_talent(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);

        $user->cvProfiles()->create([
            'talent_id' => $talent->id,
            'title' => 'CV Ana ES',
            'language' => 'es',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);
        $user->cvProfiles()->create([
            'talent_id' => $talent->id,
            'title' => 'CV Ana EN',
            'language' => 'en',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);
        $thirdProfile = $user->cvProfiles()->create([
            'title' => 'CV Ana Extra',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);

        $this->actingAs($user)
            ->patch(route('cv.talent.update', $thirdProfile), [
                'talent_id' => $talent->id,
            ])
            ->assertSessionHasErrors('talent_id');

        $this->assertNull($thirdProfile->refresh()->talent_id);
        $this->assertCount(2, $talent->refresh()->cvProfiles);
    }

    public function test_recruiter_can_download_selected_talent_cvs_as_zip(): void
    {
        $user = User::factory()->create();
        $firstTalent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $secondTalent = $user->talents()->create([
            'first_name' => 'Beto',
            'last_name' => 'Ruiz',
            'email' => 'beto@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);

        $user->cvProfiles()->create([
            'talent_id' => $firstTalent->id,
            'title' => 'CV Ana',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);
        $user->cvProfiles()->create([
            'talent_id' => $secondTalent->id,
            'title' => 'CV Beto',
            'full_name' => 'Beto Ruiz',
            'email' => 'beto@example.com',
        ]);

        $response = $this->actingAs($user)
            ->post(route('talents.download-cvs'), [
                'talent_ids' => [$firstTalent->id, $secondTalent->id],
                'cv_template_slug' => 'act-digital',
            ]);

        $response
            ->assertOk()
            ->assertDownload();

        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_recruiter_can_download_selected_talent_cvs_by_language(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);

        $user->cvProfiles()->create([
            'talent_id' => $talent->id,
            'title' => 'CV Ana ES',
            'language' => 'es',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);
        $user->cvProfiles()->create([
            'talent_id' => $talent->id,
            'title' => 'CV Ana EN',
            'language' => 'en',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);

        $response = $this->actingAs($user)
            ->post(route('talents.download-cvs'), [
                'talent_ids' => [$talent->id],
                'cv_template_slug' => 'act-digital',
                'cv_language' => 'en',
            ]);

        $response
            ->assertOk()
            ->assertDownload();

        $zipPath = tempnam(sys_get_temp_dir(), 'cvs-language-').'.zip';
        file_put_contents($zipPath, $response->streamedContent());

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($zipPath));
        $this->assertSame('cv-ana-en.pdf', $zip->getNameIndex(0));
        $zip->close();

        @unlink($zipPath);
    }

    public function test_recruiter_can_download_selected_application_cvs_as_zip(): void
    {
        $user = User::factory()->create();
        $company = $user->companies()->create(['name' => 'Acme']);
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $vacancy = $user->vacancies()->create([
            'company_id' => $company->id,
            'title' => 'Backend Developer',
            'client_company' => $company->name,
            'status' => 'open',
            'currency' => 'MXN',
        ]);
        $profile = $user->cvProfiles()->create([
            'talent_id' => $talent->id,
            'title' => 'CV Ana',
            'full_name' => 'Ana Lopez',
            'email' => 'ana@example.com',
        ]);
        $application = $user->jobApplications()->create([
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'cv_profile_id' => $profile->id,
            'status' => 'active',
            'stage' => 'review',
        ]);

        $response = $this->actingAs($user)
            ->post(route('applications.download-cvs'), [
                'application_ids' => [$application->id],
                'cv_template_slug' => 'act-digital',
            ]);

        $response
            ->assertOk()
            ->assertDownload();

        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_public_talent_link_updates_talent_and_cv(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);

        $this->put(route('public-talents.update', ['talent' => $talent->public_token]), [
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana.new@example.com',
            'headline' => 'Backend Developer',
            'technical_stack' => 'PHP, Laravel',
            'title' => 'CV actualizado',
            'full_name' => 'Ana Lopez',
            'summary' => 'Perfil actualizado',
            'skills_text' => "PHP\nLaravel",
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('public-talents.edit', ['talent' => $talent->public_token]));

        $talent->refresh();

        $this->assertSame('ana.new@example.com', $talent->email);
        $this->assertSame(['PHP', 'Laravel'], $talent->technical_stack);
        $this->assertSame('CV actualizado', $talent->cvProfile->title);
        $this->assertSame('Perfil actualizado', $talent->cvProfile->summary);
        $this->assertNotNull($talent->public_link_submitted_at);
        $this->assertDatabaseHas('cv_skills', [
            'cv_profile_id' => $talent->cvProfile->id,
            'name' => 'Laravel',
            'type' => 'skill',
        ]);
    }

    public function test_public_talent_link_can_only_be_saved_once(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);

        $payload = [
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana.new@example.com',
            'title' => 'CV Ana',
            'full_name' => 'Ana Lopez',
        ];

        $this->put(route('public-talents.update', ['talent' => $talent->public_token]), $payload)
            ->assertSessionHasNoErrors();

        $this->put(route('public-talents.update', ['talent' => $talent->public_token]), [
            ...$payload,
            'email' => 'second@example.com',
        ])
            ->assertSessionHasErrors('public_link');

        $this->assertSame('ana.new@example.com', $talent->refresh()->email);
    }

    private function talentImportFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'Nombre',
            'Apellido',
            'Fuente',
            'Estado',
            'Notas internas',
            'Ultimo contacto',
        ], null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'talents-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'talentos.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
