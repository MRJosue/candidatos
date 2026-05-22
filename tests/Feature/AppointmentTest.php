<?php

namespace Tests\Feature;

use App\Mail\AppointmentInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
        Mail::fake();

        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $company = $user->companies()->create([
            'name' => 'Acme',
            'email' => 'rrhh@acme.test',
        ]);
        $vacancy = $user->vacancies()->create([
            'company_id' => $company->id,
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

        Mail::assertSent(AppointmentInvitation::class, 2);
        Mail::assertSent(AppointmentInvitation::class, fn ($mail) => $mail->hasTo('ana@example.com'));
        Mail::assertSent(AppointmentInvitation::class, fn ($mail) => $mail->hasTo('rrhh@acme.test'));
    }

    public function test_recruiter_can_resend_appointment_invitation_to_talent_and_company(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $company = $user->companies()->create([
            'name' => 'Acme',
            'email' => 'rrhh@acme.test',
        ]);
        $vacancy = $user->vacancies()->create([
            'company_id' => $company->id,
            'title' => 'Backend Developer',
            'status' => 'open',
            'currency' => 'MXN',
        ]);
        $appointment = $user->appointments()->create([
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'scheduled_at' => now()->addDay(),
            'timezone' => 'America/Mexico_City',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)
            ->post(route('appointments.send-invitations', $appointment))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Invitacion reenviada a 2 destinatario(s).');

        Mail::assertSent(AppointmentInvitation::class, 2);
    }

    public function test_appointment_invitation_uses_cv_email_when_talent_email_is_empty(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $user->cvProfiles()->create([
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

        $response = $this->actingAs($user)->post(route('appointments.store'), [
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'timezone' => 'America/Mexico_City',
        ]);

        $response->assertSessionHasNoErrors();

        Mail::assertSent(AppointmentInvitation::class, 1);
        Mail::assertSent(AppointmentInvitation::class, fn ($mail) => $mail->hasTo('ana.cv@example.com'));
    }

    public function test_calendar_invitation_contains_google_calendar_event_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Reclutador',
            'email' => 'reclutador@example.com',
        ]);
        $talent = $user->talents()->create([
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'email' => 'ana@example.com',
            'status' => 'active',
            'currency' => 'MXN',
        ]);
        $company = $user->companies()->create([
            'name' => 'Acme',
            'email' => 'rrhh@acme.test',
        ]);
        $vacancy = $user->vacancies()->create([
            'company_id' => $company->id,
            'title' => 'Backend Developer',
            'location' => 'Remoto',
            'status' => 'open',
            'currency' => 'MXN',
        ]);
        $appointment = $user->appointments()->create([
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'scheduled_at' => '2026-06-19 11:58:00',
            'timezone' => 'America/Mexico_City',
            'status' => 'scheduled',
            'notes' => 'Test',
        ]);

        $mail = new AppointmentInvitation($appointment);
        $method = (new \ReflectionClass($mail))->getMethod('calendarInvite');
        $method->setAccessible(true);

        $calendar = $method->invoke($mail);

        $unfoldedCalendar = str_replace("\r\n ", '', $calendar);

        $this->assertStringContainsString("BEGIN:VCALENDAR\r\n", $calendar);
        $this->assertStringContainsString("METHOD:REQUEST\r\n", $calendar);
        $this->assertStringContainsString('DTSTART:20260619T175800Z', $unfoldedCalendar);
        $this->assertStringContainsString('DESCRIPTION:Candidato: Ana Lopez\nVacante: Backend Developer\nEmpresa: Acme\nNotas: Test', $unfoldedCalendar);
        $this->assertStringNotContainsString('\\\\n', $unfoldedCalendar);
        $this->assertStringContainsString('ORGANIZER;CN="Reclutador":MAILTO:reclutador@example.com', $unfoldedCalendar);
        $this->assertStringContainsString('ATTENDEE;CN="Ana Lopez";ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;', $unfoldedCalendar);
        $this->assertStringContainsString('MAILTO:ana@example.com', $unfoldedCalendar);
        $this->assertStringContainsString('MAILTO:rrhh@acme.test', $unfoldedCalendar);
        $this->assertStringContainsString("END:VCALENDAR\r\n", $calendar);
    }

    public function test_recruiter_can_delete_appointment(): void
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
        $appointment = $user->appointments()->create([
            'talent_id' => $talent->id,
            'vacancy_id' => $vacancy->id,
            'scheduled_at' => now()->addDay(),
            'timezone' => 'America/Mexico_City',
            'status' => 'scheduled',
        ]);

        $this->actingAs($user)
            ->delete(route('appointments.destroy', $appointment))
            ->assertRedirect(route('appointments.index'));

        $this->assertNull($appointment->fresh());
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
            ->assertSee('Editar')
            ->assertSee('Reenviar')
            ->assertSee('Eliminar');
    }

    public function test_index_shows_cv_email_when_talent_email_is_empty(): void
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
            'title' => 'CV Ana',
            'full_name' => 'Ana Lopez',
            'email' => 'ana.cv@example.com',
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

        $this->actingAs($user)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('ana.cv@example.com');
    }
}
