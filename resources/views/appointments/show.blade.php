<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Detalle de cita</h2></x-slot>
    <div class="py-8"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow-sm space-y-4">
        <p><strong>Candidato:</strong> {{ $appointment->talent?->full_name ?? 'Candidato no disponible' }}</p>
        <p><strong>Vacante:</strong> {{ $appointment->vacancy?->display_title ?? 'Vacante no disponible' }}</p>
        <p><strong>Empresa:</strong> {{ $appointment->vacancy?->display_company ?? 'Cliente confidencial' }}</p>
        <p><strong>Fecha:</strong> {{ $appointment->scheduled_at->format('d/m/Y H:i') }} {{ $appointment->timezone }}</p>
        <p><strong>Estatus:</strong> {{ $appointment->status }}</p>
        <p>{{ $appointment->notes }}</p>
        <div class="flex gap-3">
            <a href="{{ route('appointments.edit', $appointment) }}" class="text-indigo-700">Editar</a>
            <form method="POST" action="{{ route('appointments.destroy', $appointment) }}">@csrf @method('DELETE')<button class="text-red-700">Cancelar</button></form>
        </div>
    </div></div>
</x-app-layout>
