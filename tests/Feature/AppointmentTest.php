<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_appointment_shows_talents_and_vacancies(): void
    {
        $user = User::factory()->create();

        $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $user->vacancies()->create([
            'title' => 'Backend Developer',
            'status' => 'open',
            'currency' => 'MXN',
        ]);

        $this->actingAs($user)
            ->get(route('appointments.create'))
            ->assertOk()
            ->assertSee('Selecciona un candidato')
            ->assertSee('Ana Lopez')
            ->assertSee('Selecciona una vacante')
            ->assertSee('Backend Developer');
    }

    public function test_create_appointment_explains_when_talents_or_vacancies_are_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('appointments.create'))
            ->assertOk()
            ->assertSee('Necesitas al menos un candidato y una vacante para agendar una cita.');
    }

    public function test_recruiter_can_create_appointment_for_talent_and_vacancy(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $vacancy = $user->vacancies()->create([
            'title' => 'Backend Developer',
            'status' => 'open',
            'currency' => 'MXN',
        ]);

        $response = $this->actingAs($user)->post(route('appointments.store'), [
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'timezone' => 'America/Mexico_City',
            'notes' => 'Entrevista tecnica',
        ]);

        $appointment = $user->appointments()->first();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('appointments.show', $appointment));

        $this->assertSame($talent->id, $appointment->talent_id);
        $this->assertSame($vacancy->id, $appointment->vacancy_id);
        $this->assertSame('scheduled', $appointment->status);
    }

    public function test_index_shows_calendar_tab_with_current_appointments(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $vacancy = $user->vacancies()->create([
            'title' => 'Backend Developer',
            'status' => 'open',
            'currency' => 'MXN',
        ]);

        $user->appointments()->create([
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'scheduled_at' => '2026-05-20 10:30:00',
            'timezone' => 'America/Mexico_City',
            'status' => 'scheduled',
        ]);

        $user->appointments()->create([
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'scheduled_at' => '2026-05-21 11:00:00',
            'timezone' => 'America/Mexico_City',
            'status' => 'cancelled',
        ]);

        $this->actingAs($user)
            ->get(route('appointments.index', ['tab' => 'calendar', 'month' => '2026-05']))
            ->assertOk()
            ->assertSee('Calendario')
            ->assertSee('Calendario de citas')
            ->assertSee('Ana Lopez')
            ->assertSee('Backend Developer')
            ->assertSee('10:30')
            ->assertDontSee('11:00');
    }

    public function test_index_shows_appointments_in_table_layout(): void
    {
        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $vacancy = $user->vacancies()->create([
            'title' => 'Backend Developer',
            'client_company' => 'Acme',
            'status' => 'open',
            'currency' => 'MXN',
        ]);

        $user->appointments()->create([
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'scheduled_at' => '2026-05-20 10:30:00',
            'timezone' => 'America/Mexico_City',
            'status' => 'scheduled',
            'notes' => 'Entrevista tecnica',
        ]);

        $this->actingAs($user)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Candidato')
            ->assertSee('Vacante')
            ->assertSee('Empresa')
            ->assertSee('Fecha')
            ->assertSee('Acciones')
            ->assertSee('Ana Lopez')
            ->assertSee('ana@example.com')
            ->assertSee('Backend Developer')
            ->assertSee('Acme')
            ->assertSee('Agendada')
            ->assertSee('Ver')
            ->assertSee('Editar');
    }
}
