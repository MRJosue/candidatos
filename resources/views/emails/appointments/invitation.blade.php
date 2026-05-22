@php
    $appointment->loadMissing(['talent', 'vacancy.company', 'vacancy.position', 'user']);
@endphp

<p>Hola,</p>

<p>Se ha agendado una cita con la siguiente informacion:</p>

<ul>
    <li><strong>Candidato:</strong> {{ $appointment->talent?->full_name ?? 'Candidato no disponible' }}</li>
    <li><strong>Vacante:</strong> {{ $appointment->vacancy?->display_title ?? 'Vacante no disponible' }}</li>
    <li><strong>Empresa:</strong> {{ $appointment->vacancy?->display_company ?? 'Cliente confidencial' }}</li>
    <li><strong>Fecha:</strong> {{ $appointment->scheduled_at->format('d/m/Y H:i') }} {{ $appointment->timezone }}</li>
</ul>

@if ($appointment->notes)
    <p><strong>Notas:</strong><br>{{ nl2br(e($appointment->notes)) }}</p>
@endif

<p>Adjuntamos una invitacion de calendario para agregarla a Google Calendar u otro calendario compatible.</p>

<p>Saludos,<br>{{ $appointment->user?->name ?? config('app.name') }}</p>
