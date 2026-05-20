<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\Position;
use App\Models\Talent;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AdminRecruitingDemoSeeder extends Seeder
{
    /**
     * Seed demo recruiting data for admin users only.
     */
    public function run(): void
    {
        Role::findOrCreate('admin');

        $admins = User::role('admin')->get();

        if ($admins->isEmpty()) {
            $this->command?->warn('No admin users found. Run DatabaseSeeder or assign the admin role before seeding demo recruiting data.');

            return;
        }

        $admins->each(fn (User $admin) => $this->seedForAdmin($admin));
    }

    private function seedForAdmin(User $admin): void
    {
        DB::transaction(function () use ($admin): void {
            $companies = $this->seedCompanies($admin);
            $vacancies = $this->seedVacancies($admin, $companies);
            $talents = $this->seedTalents($admin);

            $this->seedApplications($admin, $talents, $vacancies);
        });
    }

    /**
     * @return array<string, Company>
     */
    private function seedCompanies(User $admin): array
    {
        $data = [
            'act-digital' => [
                'name' => 'ACT Digital',
                'industry' => 'Consultoria tecnologica',
                'website_url' => 'https://actdigital.com',
                'location' => 'Ciudad de Mexico, Mexico',
                'notes' => 'Cliente principal para perfiles digitales, cloud y desarrollo de software.',
            ],
            'norte-fintech' => [
                'name' => 'Norte Fintech',
                'industry' => 'Servicios financieros',
                'website_url' => 'https://nortefintech.example',
                'location' => 'Monterrey, Mexico',
                'notes' => 'Fintech en crecimiento con foco en pagos, compliance y analitica.',
            ],
            'salud-connect' => [
                'name' => 'Salud Connect',
                'industry' => 'Healthtech',
                'website_url' => 'https://saludconnect.example',
                'location' => 'Guadalajara, Mexico',
                'notes' => 'Producto SaaS para clinicas y seguimiento de pacientes.',
            ],
        ];

        return collect($data)
            ->mapWithKeys(fn (array $company, string $key) => [
                $key => Company::updateOrCreate(
                    [
                        'recruiter_id' => $admin->id,
                        'name' => $company['name'],
                    ],
                    $company + ['recruiter_id' => $admin->id],
                ),
            ])
            ->all();
    }

    /**
     * @param  array<string, Company>  $companies
     * @return array<string, Vacancy>
     */
    private function seedVacancies(User $admin, array $companies): array
    {
        $data = [
            'backend-laravel' => [
                'company' => 'act-digital',
                'position' => [
                    'title' => 'Backend Developer Laravel',
                    'department' => 'Engineering',
                    'seniority' => 'Senior',
                    'employment_type' => 'Tiempo completo',
                    'work_mode' => 'Remoto',
                    'location' => 'Mexico',
                    'salary_min' => 65000,
                    'salary_max' => 85000,
                    'currency' => 'MXN',
                    'technical_stack' => ['PHP', 'Laravel', 'MySQL', 'Redis', 'AWS'],
                    'description' => 'Desarrollo de APIs, integraciones y modulos internos para productos digitales.',
                    'requirements' => 'Experiencia solida con Laravel, pruebas automatizadas, SQL y despliegues cloud.',
                ],
                'vacancy' => [
                    'status' => 'open',
                    'opened_at' => now()->subDays(8),
                ],
            ],
            'frontend-react' => [
                'company' => 'norte-fintech',
                'position' => [
                    'title' => 'Frontend Engineer React',
                    'department' => 'Producto',
                    'seniority' => 'Mid',
                    'employment_type' => 'Tiempo completo',
                    'work_mode' => 'Hibrido',
                    'location' => 'Monterrey, Mexico',
                    'salary_min' => 45000,
                    'salary_max' => 62000,
                    'currency' => 'MXN',
                    'technical_stack' => ['React', 'TypeScript', 'Tailwind CSS', 'REST'],
                    'description' => 'Construccion de interfaces para dashboards financieros y flujos transaccionales.',
                    'requirements' => 'Dominio de React, manejo de estado, consumo de APIs y sensibilidad por UX.',
                ],
                'vacancy' => [
                    'status' => 'open',
                    'opened_at' => now()->subDays(5),
                ],
            ],
            'data-analyst' => [
                'company' => 'salud-connect',
                'position' => [
                    'title' => 'Data Analyst',
                    'department' => 'Data',
                    'seniority' => 'Junior',
                    'employment_type' => 'Tiempo completo',
                    'work_mode' => 'Remoto',
                    'location' => 'Mexico',
                    'salary_min' => 30000,
                    'salary_max' => 42000,
                    'currency' => 'MXN',
                    'technical_stack' => ['SQL', 'Power BI', 'Excel', 'Python'],
                    'description' => 'Analisis de datos operativos, dashboards y metricas de seguimiento clinico.',
                    'requirements' => 'Buen nivel de SQL, modelado de datos y comunicacion con equipos no tecnicos.',
                ],
                'vacancy' => [
                    'status' => 'paused',
                    'opened_at' => now()->subDays(18),
                ],
            ],
        ];

        return collect($data)
            ->mapWithKeys(function (array $item, string $key) use ($admin, $companies): array {
                $company = $companies[$item['company']];
                $positionData = $item['position'] + [
                    'recruiter_id' => $admin->id,
                    'company_id' => $company->id,
                ];

                $position = Position::updateOrCreate(
                    [
                        'recruiter_id' => $admin->id,
                        'company_id' => $company->id,
                        'title' => $item['position']['title'],
                    ],
                    $positionData,
                );

                $vacancy = Vacancy::updateOrCreate(
                    [
                        'recruiter_id' => $admin->id,
                        'company_id' => $company->id,
                        'position_id' => $position->id,
                    ],
                    [
                        'title' => $position->title,
                        'client_company' => $company->name,
                        'location' => $position->location,
                        'work_mode' => $position->work_mode,
                        'employment_type' => $position->employment_type,
                        'seniority' => $position->seniority,
                        'salary_min' => $position->salary_min,
                        'salary_max' => $position->salary_max,
                        'currency' => $position->currency,
                        'technical_stack' => $position->technical_stack,
                        'description' => $position->description,
                        'requirements' => $position->requirements,
                    ] + $item['vacancy'],
                );

                return [$key => $vacancy];
            })
            ->all();
    }

    /**
     * @return array<string, Talent>
     */
    private function seedTalents(User $admin): array
    {
        $data = [
            'ana-lopez' => [
                'first_name' => 'Ana',
                'last_name' => 'Lopez',
                'email' => 'ana.lopez.demo@example.com',
                'phone' => '+52 55 1200 0101',
                'location' => 'Ciudad de Mexico, Mexico',
                'headline' => 'Backend developer enfocada en APIs y automatizacion',
                'target_position' => 'Backend Developer Laravel',
                'seniority' => 'Senior',
                'source' => 'LinkedIn',
                'status' => 'active',
                'availability' => '2 semanas',
                'salary_expectation_min' => 68000,
                'salary_expectation_max' => 82000,
                'currency' => 'MXN',
                'technical_stack' => ['PHP', 'Laravel', 'MySQL', 'Redis', 'Docker'],
                'languages' => ['Espanol nativo', 'Ingles B2'],
                'links' => ['https://linkedin.com/in/ana-lopez-demo'],
                'technical_summary' => 'Ha liderado integraciones REST, migraciones de datos y automatizacion de procesos internos.',
                'notes' => 'Perfil fuerte para vacantes backend senior.',
                'last_contacted_at' => now()->subDays(2),
            ],
            'marco-ruiz' => [
                'first_name' => 'Marco',
                'last_name' => 'Ruiz',
                'email' => 'marco.ruiz.demo@example.com',
                'phone' => '+52 81 1200 0202',
                'location' => 'Monterrey, Mexico',
                'headline' => 'Frontend engineer con foco en productos fintech',
                'target_position' => 'Frontend Engineer React',
                'seniority' => 'Mid',
                'source' => 'Referido',
                'status' => 'active',
                'availability' => 'Inmediata',
                'salary_expectation_min' => 47000,
                'salary_expectation_max' => 60000,
                'currency' => 'MXN',
                'technical_stack' => ['React', 'TypeScript', 'Tailwind CSS', 'Jest'],
                'languages' => ['Espanol nativo', 'Ingles B1'],
                'links' => ['https://github.com/marco-ruiz-demo'],
                'technical_summary' => 'Experiencia construyendo componentes reutilizables, dashboards y flujos responsivos.',
                'notes' => 'Buen fit para producto financiero con acompanamiento senior.',
                'last_contacted_at' => now()->subDays(1),
            ],
            'sofia-mendez' => [
                'first_name' => 'Sofia',
                'last_name' => 'Mendez',
                'email' => 'sofia.mendez.demo@example.com',
                'phone' => '+52 33 1200 0303',
                'location' => 'Guadalajara, Mexico',
                'headline' => 'Analista de datos con experiencia en BI operativo',
                'target_position' => 'Data Analyst',
                'seniority' => 'Junior',
                'source' => 'Bolsa de trabajo',
                'status' => 'active',
                'availability' => '1 mes',
                'salary_expectation_min' => 32000,
                'salary_expectation_max' => 40000,
                'currency' => 'MXN',
                'technical_stack' => ['SQL', 'Power BI', 'Excel', 'Python'],
                'languages' => ['Espanol nativo', 'Ingles A2'],
                'links' => ['https://linkedin.com/in/sofia-mendez-demo'],
                'technical_summary' => 'Crea tableros operativos, limpia fuentes de datos y documenta metricas para equipos de negocio.',
                'notes' => 'Interesante para posiciones junior de datos.',
                'last_contacted_at' => now()->subDays(6),
            ],
            'diego-santos' => [
                'first_name' => 'Diego',
                'last_name' => 'Santos',
                'email' => 'diego.santos.demo@example.com',
                'phone' => '+52 55 1200 0404',
                'location' => 'Puebla, Mexico',
                'headline' => 'Full stack developer con experiencia en SaaS',
                'target_position' => 'Full Stack Developer',
                'seniority' => 'Senior',
                'source' => 'Base interna',
                'status' => 'paused',
                'availability' => 'Por confirmar',
                'salary_expectation_min' => 70000,
                'salary_expectation_max' => 90000,
                'currency' => 'MXN',
                'technical_stack' => ['Laravel', 'Vue', 'PostgreSQL', 'AWS'],
                'languages' => ['Espanol nativo', 'Ingles B2'],
                'links' => ['https://linkedin.com/in/diego-santos-demo'],
                'technical_summary' => 'Ha trabajado en SaaS B2B, modulos administrativos y optimizacion de consultas.',
                'notes' => 'Mantener en pipeline para roles full stack.',
                'last_contacted_at' => now()->subDays(10),
            ],
        ];

        return collect($data)
            ->mapWithKeys(fn (array $talent, string $key) => [
                $key => Talent::updateOrCreate(
                    [
                        'recruiter_id' => $admin->id,
                        'email' => $talent['email'],
                    ],
                    $talent + ['recruiter_id' => $admin->id],
                ),
            ])
            ->all();
    }

    /**
     * @param  array<string, Talent>  $talents
     * @param  array<string, Vacancy>  $vacancies
     */
    private function seedApplications(User $admin, array $talents, array $vacancies): void
    {
        $data = [
            [
                'talent' => 'ana-lopez',
                'vacancy' => 'backend-laravel',
                'status' => 'active',
                'stage' => 'technical_interview',
                'match_score' => 92,
                'notes' => 'Avanza a entrevista tecnica con cliente.',
                'applied_at' => now()->subDays(6),
                'last_activity_at' => now()->subDay(),
            ],
            [
                'talent' => 'marco-ruiz',
                'vacancy' => 'frontend-react',
                'status' => 'active',
                'stage' => 'review',
                'match_score' => 84,
                'notes' => 'Pendiente validar experiencia en productos fintech.',
                'applied_at' => now()->subDays(3),
                'last_activity_at' => now()->subDays(2),
            ],
            [
                'talent' => 'sofia-mendez',
                'vacancy' => 'data-analyst',
                'status' => 'applied',
                'stage' => JobApplication::DEFAULT_STAGE,
                'match_score' => 78,
                'notes' => 'Perfil alineado a BI operativo.',
                'applied_at' => now()->subDays(12),
                'last_activity_at' => now()->subDays(5),
            ],
        ];

        foreach ($data as $application) {
            JobApplication::updateOrCreate(
                [
                    'talent_id' => $talents[$application['talent']]->id,
                    'vacancy_id' => $vacancies[$application['vacancy']]->id,
                ],
                [
                    'recruiter_id' => $admin->id,
                    'status' => $application['status'],
                    'stage' => $application['stage'],
                    'match_score' => $application['match_score'],
                    'notes' => $application['notes'],
                    'applied_at' => $application['applied_at'],
                    'last_activity_at' => $application['last_activity_at'],
                ],
            );
        }
    }
}
