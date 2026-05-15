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
}
