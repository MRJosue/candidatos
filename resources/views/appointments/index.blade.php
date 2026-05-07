<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Citas</h2>
            <a href="{{ route('appointments.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded">Nueva cita</a>
        </div>
    </x-slot>
    <div class="py-8"><div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if (session('status'))<div class="bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>@endif
        @foreach ($appointments as $appointment)
            <a href="{{ route('appointments.show', $appointment) }}" class="block bg-white p-5 rounded shadow-sm">
                {{ $appointment->service->name }} · {{ $appointment->scheduled_at->format('d/m/Y H:i') }} · {{ $appointment->status }}
            </a>
        @endforeach
        {{ $appointments->links() }}
    </div></div>
</x-app-layout>
