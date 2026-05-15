<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo tema</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Cargar tema por JSON</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Sube un archivo .json o pega el contenido para rellenar el formulario de nuevo tema.
                        </p>

                        @if (session('status') === 'theme-json-loaded')
                            <p class="mt-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                                JSON cargado. Revisa los campos y guarda el tema.
                            </p>
                        @endif

                        <form method="POST" action="{{ route('admin.themes.import-json') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                            @csrf

                            <div>
                                <x-input-label for="theme_json_file" value="Archivo JSON" />
                                <input id="theme_json_file" name="theme_json_file" type="file" accept="application/json,.json" class="mt-1 block w-full text-sm text-gray-600 file:me-4 file:rounded-md file:border-0 file:bg-amber-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-amber-800">
                            </div>

                            <div>
                                <x-input-label for="theme_json" value="Contenido JSON" />
                                <textarea id="theme_json" name="theme_json" rows="8" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500" placeholder='{"name":"Tema bosque","light_palette":{...},"dark_palette":{...}}'>{{ old('theme_json') }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('theme_json')" />
                            </div>

                            <x-secondary-button type="submit">Cargar JSON</x-secondary-button>
                        </form>
                    </div>

                    <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                        <h4 class="text-sm font-semibold text-gray-900">Formato esperado</h4>
                        <pre class="mt-3 overflow-x-auto text-xs text-gray-600"><code>{
  "name": "Tema bosque",
  "slug": "bosque",
  "description": "Opcional",
  "is_active": true,
  "is_default": false,
  "light_palette": {
    "bg": "#f6f7ef",
    "surface": "#ffffff",
    "surface-muted": "#e8eddf",
    "surface-soft": "#f1f5e8",
    "border": "#cfd8c0",
    "text": "#182217",
    "text-muted": "#586852",
    "accent": "#2f7d46",
    "accent-hover": "#236238"
  },
  "dark_palette": {
    "bg": "#0f1711",
    "surface": "#172219",
    "surface-muted": "#203125",
    "surface-soft": "#263a2c",
    "border": "#38513f",
    "text": "#eef7ed",
    "text-muted": "#b7c9b5",
    "accent": "#74c68a",
    "accent-hover": "#9adeaa"
  }
}</code></pre>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.themes.store') }}" enctype="multipart/form-data">
                @include('admin.themes._form')
            </form>
        </div>
    </div>
</x-app-layout>
