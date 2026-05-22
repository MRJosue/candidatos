<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Editar CV</h2></x-slot>
    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded">
                    {{ session('status') }}
                </div>
            @endif

            @include('cv._document_import', [
                'action' => route('cv.import-document-ai', $profile),
                'profile' => $profile,
                'talent' => $profile->talent,
            ])

            @include('cv._document_import_preview', [
                'applyAction' => route('cv.apply-document-import', $profile),
            ])

            <form method="POST" action="{{ route('cv.update', $profile) }}" class="space-y-6">
                @method('PUT')

                <section class="bg-white p-6 rounded shadow-sm space-y-5">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Datos principales</h3>
                    @include('cv._form', ['showSubmitButton' => false])
                </section>

                @include('cv._sections_form')

                <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
            </form>
        </div>
    </div>
</x-app-layout>
