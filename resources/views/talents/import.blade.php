<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Carga masiva de talentos</h2>
                <p class="text-sm text-gray-500">Descarga el layout, completa los registros y valida la previsualizacion antes de guardar.</p>
            </div>
            <a href="/talents" class="px-4 py-2 bg-gray-100 text-gray-700 rounded text-sm text-center">Volver</a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ saving: false }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 text-red-700 p-4 rounded">{{ $errors->first() }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                <section class="bg-white rounded shadow-sm p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">1. Descarga el layout</h3>
                        <p class="mt-1 text-sm text-gray-500">Las columnas obligatorias estan resaltadas en amarillo dentro del archivo.</p>
                    </div>

                    <a href="/talents/import/layout" class="inline-flex items-center justify-center px-4 py-2 bg-gray-900 text-white rounded text-sm">
                        Descargar layout Excel
                    </a>

                    <div class="overflow-hidden rounded border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Campo</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Tipo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($columns as $column)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-800">{{ $column['label'] }}</td>
                                        <td class="px-4 py-3">
                                            @if ($column['required'])
                                                <span class="inline-flex rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Obligatorio</span>
                                            @else
                                                <span class="text-gray-500">Opcional</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="bg-white rounded shadow-sm p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">2. Carga tu archivo</h3>
                        <p class="mt-1 text-sm text-gray-500">Acepta archivos .xlsx, .xls y .csv con la misma estructura del layout.</p>
                    </div>

                    <form method="POST" action="/talents/import/preview" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <label class="block">
                            <span class="text-sm text-gray-700">Archivo de talentos</span>
                            <input type="file" name="talents_file" accept=".xlsx,.xls,.csv" class="mt-1 block w-full rounded border border-gray-300 text-sm file:mr-4 file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:text-gray-700" required>
                        </label>

                        <button class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Previsualizar</button>
                    </form>
                </section>
            </div>

            @if ($preview)
                @php
                    $canImport = ! $preview['has_errors'] && $preview['valid_count'] > 0;
                    $previewColumns = ['first_name' => 'Nombre', 'last_name' => 'Apellido', 'email' => 'Email', 'target_position' => 'Puesto objetivo', 'status' => 'Estado', 'currency' => 'Moneda'];
                @endphp

                <section class="bg-white rounded shadow-sm overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-gray-200 p-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Previsualizacion</h3>
                            <p class="text-sm text-gray-500">
                                {{ $preview['total_count'] }} registros detectados,
                                {{ $preview['valid_count'] }} listos para cargar,
                                {{ $preview['error_count'] }} con errores.
                            </p>
                        </div>

                        <form method="POST" action="/talents/import/store" x-on:submit="saving = true">
                            @csrf
                            <button class="px-4 py-2 bg-gray-900 text-white rounded text-sm disabled:cursor-not-allowed disabled:opacity-50" @disabled(! $canImport)>
                                Cargar registros
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fila</th>
                                    @foreach ($previewColumns as $label)
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $label }}</th>
                                    @endforeach
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Validacion</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($preview['rows'] as $row)
                                    <tr class="{{ count($row['errors']) ? 'bg-red-50' : '' }}">
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $row['number'] }}</td>
                                        @foreach ($previewColumns as $field => $label)
                                            <td class="px-6 py-4 text-sm text-gray-800">{{ is_array($row['data'][$field] ?? null) ? implode(', ', $row['data'][$field]) : ($row['data'][$field] ?? '-') }}</td>
                                        @endforeach
                                        <td class="px-6 py-4 text-sm">
                                            @if (count($row['errors']))
                                                <ul class="list-disc pl-4 text-red-700">
                                                    @foreach ($row['errors'] as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-emerald-700">Lista</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>

        <div x-cloak x-show="saving" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/70">
            <div class="w-full max-w-sm rounded bg-white p-6 text-center shadow-xl">
                <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-gray-200 border-t-gray-900"></div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">Guardando datos</h3>
                <p class="mt-1 text-sm text-gray-500">Estamos creando los nuevos talentos. Esta pantalla se cerrara al terminar.</p>
            </div>
        </div>
    </div>
</x-app-layout>
