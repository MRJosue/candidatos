<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
                        'ana@example.com',
                        '5551234567',
                        'Ciudad de Mexico',
                        'Backend Developer',
                        'Backend Developer',
                        'Senior',
                        'LinkedIn',
                        'active',
                        'Inmediata',
                        '45000',
                        '55000',
                        'MXN',
                        'PHP, Laravel, MySQL',
                        'Espanol, Ingles B2',
                        'https://linkedin.com/in/ana',
                        'Construye APIs y paneles internos.',
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
        $this->assertSame(['PHP', 'Laravel', 'MySQL'], $talent->technical_stack);
        $this->assertDatabaseHas('talents', [
            'recruiter_id' => $user->id,
            'email' => 'ana@example.com',
            'currency' => 'MXN',
        ]);
    }

    public function test_recruiter_can_manage_companies(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('companies.store'), [
            'name' => 'Acme',
            'industry' => 'Software',
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

        $this->actingAs($user)
            ->put(route('companies.update', $company), [
                'name' => 'Acme Labs',
                'industry' => 'Tecnologia',
                'website_url' => 'https://example.com',
                'location' => 'Guadalajara',
                'notes' => 'Cliente recurrente',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('companies.show', $company));

        $company->refresh();

        $this->assertSame('Acme Labs', $company->name);
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
            'stage' => 'screening',
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
                'stage' => 'interview',
                'match_score' => 90,
                'notes' => 'Avanza a entrevista',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('applications.show', $application));

        $application->refresh();

        $this->assertSame('active', $application->status);
        $this->assertSame('interview', $application->stage);
        $this->assertSame(90, $application->match_score);

        $this->actingAs($user)
            ->delete(route('applications.destroy', $application))
            ->assertRedirect(route('applications.index'));

        $this->assertNull($application->fresh());
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
            ->assertRedirect(route('talents.show', $talent));

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
            'cv_title' => 'CV actualizado',
            'summary' => 'Perfil actualizado',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('public-talents.edit', ['talent' => $talent->public_token]));

        $talent->refresh();

        $this->assertSame('ana.new@example.com', $talent->email);
        $this->assertSame(['PHP', 'Laravel'], $talent->technical_stack);
        $this->assertSame('CV actualizado', $talent->cvProfile->title);
        $this->assertSame('Perfil actualizado', $talent->cvProfile->summary);
    }

    private function talentImportFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            'Nombre',
            'Apellido',
            'Email',
            'Telefono',
            'Ubicacion',
            'Headline',
            'Puesto objetivo',
            'Senioridad',
            'Fuente',
            'Estado',
            'Disponibilidad',
            'Expectativa minima',
            'Expectativa maxima',
            'Moneda',
            'Stack tecnico',
            'Idiomas',
            'Links',
            'Resumen tecnico',
            'Notas internas',
            'Ultimo contacto',
        ], null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'talents-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'talentos.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
