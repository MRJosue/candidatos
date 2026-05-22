<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Nuevo CV</h2></x-slot>
    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            @include('cv._document_import', [
                'action' => route('cv.import-document-ai-create'),
                'profile' => $profile,
                'talent' => $talent ?? null,
            ])

            @include('cv._document_import_preview')

            <section class="bg-white p-6 rounded shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Datos principales</h3>
                <form method="POST" action="{{ route('cv.store') }}" class="space-y-4">
                    @include('cv._document_import_options')
                    @include('cv._form', ['showSubmitButton' => false])
                    @include('cv._sections_form', ['sectionText' => $sectionText ?? []])
                    <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
