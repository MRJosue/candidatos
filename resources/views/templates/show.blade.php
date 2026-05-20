<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ $template->name }}</h2></x-slot>
    <div class="py-8"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow-sm space-y-4">
        <p>{{ $template->description }}</p>
        @if ($template->is_premium)
            <p class="text-sm text-gray-500">Premium · {{ $template->currency }} {{ number_format($template->price_cents / 100, 2) }}</p>
        @endif
        @auth
            @if ($template->is_premium)
                <form method="POST" action="{{ route('templates.purchase', $template) }}">
                    @csrf
                    <button class="px-4 py-2 bg-gray-900 text-white rounded">Comprar plantilla</button>
                </form>
            @endif
        @endauth
    </div></div>
</x-app-layout>
