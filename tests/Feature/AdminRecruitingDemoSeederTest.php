<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\Talent;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\AdminRecruitingDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRecruitingDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_recruiting_demo_data_only_for_admin_users(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('cliente');

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $client = User::factory()->create(['email' => 'client@example.com']);

        $admin->assignRole('admin');
        $client->assignRole('cliente');

        $this->seed(AdminRecruitingDemoSeeder::class);

        $this->assertSame(3, $admin->companies()->count());
        $this->assertSame(4, $admin->talents()->count());
        $this->assertSame(3, $admin->vacancies()->count());
        $this->assertSame(3, $admin->jobApplications()->count());

        $this->assertSame(0, $client->companies()->count());
        $this->assertSame(0, $client->talents()->count());
        $this->assertSame(0, $client->vacancies()->count());

        $vacancy = Vacancy::where('title', 'Backend Developer Laravel')->firstOrFail();

        $this->assertSame('ACT Digital', $vacancy->company->name);
        $this->assertSame('Backend Developer Laravel', $vacancy->position->title);
        $this->assertSame(['PHP', 'Laravel', 'MySQL', 'Redis', 'AWS'], $vacancy->technical_stack);
        $this->assertTrue(JobApplication::whereBelongsTo($vacancy)->exists());

        $this->assertTrue(Talent::where('email', 'ana.lopez.demo@example.com')->exists());
        $this->assertTrue(Company::where('name', 'Norte Fintech')->exists());
    }

    public function test_it_is_idempotent(): void
    {
        Role::findOrCreate('admin');

        User::factory()
            ->create(['email' => 'admin@example.com'])
            ->assignRole('admin');

        $this->seed(AdminRecruitingDemoSeeder::class);
        $this->seed(AdminRecruitingDemoSeeder::class);

        $this->assertSame(3, Company::count());
        $this->assertSame(4, Talent::count());
        $this->assertSame(3, Vacancy::count());
        $this->assertSame(3, JobApplication::count());
    }
}
