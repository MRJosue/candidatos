<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Compras</h2></x-slot>
    <div class="py-8"><div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if (session('status'))<div class="bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>@endif
        @forelse ($purchases as $purchase)
            <div class="bg-white p-5 rounded shadow-sm flex justify-between">
                <span>{{ $purchase->template?->name ?? 'Plantilla' }}</span>
                <span>{{ strtoupper($purchase->status) }} · {{ $purchase->currency }} {{ number_format($purchase->amount_cents / 100, 2) }}</span>
            </div>
        @empty
            <div class="bg-white p-6 rounded shadow-sm text-gray-600">Todavia no tienes compras.</div>
        @endforelse
        {{ $purchases->links() }}
    </div></div>
</x-app-layout>
