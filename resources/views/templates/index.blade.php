<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Plantillas</h2></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid md:grid-cols-2 gap-6">
        @foreach ($templates as $template)
            <article class="bg-white p-6 rounded shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-lg">{{ $template->name }}</h3>
                        <p class="text-gray-600 mt-2">{{ $template->description }}</p>
                    </div>
                    <span class="text-sm px-2 py-1 rounded {{ $template->is_premium ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                        {{ $template->is_premium ? '$'.number_format($template->price_cents / 100, 2).' '.$template->currency : 'Gratis' }}
                    </span>
                </div>
                <div class="mt-4 flex gap-3">
                    <a href="{{ route('templates.show', $template) }}" class="text-indigo-700">Ver detalle</a>
                    @if ($template->is_premium && ! in_array($template->id, $purchasedTemplateIds))
                        <form method="POST" action="{{ route('templates.purchase', $template) }}">
                            @csrf
                            <button class="text-indigo-700">Comprar</button>
                        </form>
                    @endif
                </div>
            </article>
        @endforeach
    </div></div>
</x-app-layout>
