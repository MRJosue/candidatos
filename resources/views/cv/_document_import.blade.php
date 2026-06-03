<section class="bg-white p-6 rounded shadow-sm" x-data="{ processing: false, unsupportedDoc: false }">
    <style>[x-cloak] { display: none !important; }</style>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Crear CV con IA</h3>
            <p class="text-sm text-gray-500">Sube un PDF con texto real, DOCX o TXT. La IA preparara una previsualizacion y solo se guardara cuando confirmes.</p>
        </div>
        <span class="text-xs font-medium uppercase tracking-wide text-indigo-600">Demo IA</span>
    </div>

    <details class="mt-4 rounded border border-amber-200 bg-amber-50/70 p-4 text-sm text-amber-900">
        <summary class="cursor-pointer font-medium">Sugerencias para documentos no soportados</summary>
        <div class="mt-3 space-y-2 text-amber-800">
            <p>Si tu archivo termina en <strong>.doc</strong>, este servidor no puede leerlo directamente.</p>
            <p>Abre el documento en Word, Google Docs o LibreOffice y usa una de estas opciones:</p>
            <ul class="list-disc ps-5 space-y-1">
                <li>Guardar como <strong>.docx</strong>.</li>
                <li>Guardar como <strong>.txt</strong>.</li>
                <li>Copiar todo el contenido del CV a un archivo <strong>.txt</strong> y subir ese TXT para procesarlo.</li>
            </ul>
        </div>
    </details>

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-5 grid gap-4 md:grid-cols-[1fr_auto] md:items-end" x-on:submit="if (unsupportedDoc) { $event.preventDefault(); return; } processing = true">
        @csrf
        @if (filled($talent?->id ?? null))
            <input type="hidden" name="talent_id" value="{{ $talent->id }}">
        @elseif (filled(old('talent_id', $profile->talent_id ?? null)))
            <input type="hidden" name="talent_id" value="{{ old('talent_id', $profile->talent_id ?? null) }}">
        @endif
        <label class="block">
            <span class="text-sm text-gray-700">Documento del CV</span>
            <input
                type="file"
                name="cv_document"
                accept=".pdf,.docx,.txt,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain"
                class="mt-1 block w-full rounded border border-gray-300 text-sm file:mr-4 file:border-0 file:bg-indigo-100 file:px-4 file:py-2 file:text-sm file:text-indigo-800"
                x-on:change="unsupportedDoc = ($event.target.files[0]?.name || '').toLowerCase().endsWith('.doc')"
                required
            >
        </label>
        <button
            type="submit"
            class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70"
            x-bind:disabled="processing || unsupportedDoc"
        >
            Analizar cv
        </button>
        <p x-cloak x-show="unsupportedDoc" class="text-sm text-red-700 md:col-span-2">Los archivos .doc no están soportados aquí. Guarda el contenido como TXT o conviértelo a DOCX antes de analizarlo.</p>
        @error('cv_document')
            <p class="text-sm text-red-700 md:col-span-2">{{ $message }}</p>
        @enderror
        @error('cv_document_ai')
            <p class="text-sm text-red-700 md:col-span-2">{{ $message }}</p>
        @enderror
    </form>

    <div
        x-cloak
        x-show="processing"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cv-ai-processing-title"
    >
        <div class="w-full max-w-sm rounded bg-white p-6 text-center shadow-xl">
            <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></div>
            <h3 id="cv-ai-processing-title" class="mt-4 text-lg font-semibold text-gray-900">Estamos procesando su solicitud.</h3>
            <p class="mt-2 text-sm text-gray-500">El analisis puede tardar unos momentos.</p>
        </div>
    </div>
</section>
