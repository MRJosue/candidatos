@if (! empty($documentImport))
    @php($parsed = $documentImport['parsed'] ?? [])
    <section class="bg-white p-6 rounded shadow-sm space-y-5">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Previsualizacion de IA</h3>
            <p class="text-sm text-gray-500">Archivo: {{ $documentImport['original_name'] ?? 'documento cargado' }}. Al aplicar se reemplazaran las secciones seleccionadas.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div class="rounded border border-gray-200 p-4">
                <h4 class="font-semibold text-gray-900">Perfil</h4>
                <dl class="mt-3 space-y-2 text-gray-600">
                    <div><dt class="font-medium text-gray-800">Nombre</dt><dd>{{ $parsed['profile']['full_name'] ?? 'No detectado' }}</dd></div>
                    <div><dt class="font-medium text-gray-800">Email</dt><dd>{{ $parsed['profile']['email'] ?? 'No detectado' }}</dd></div>
                    <div><dt class="font-medium text-gray-800">Telefono</dt><dd>{{ $parsed['profile']['phone'] ?? 'No detectado' }}</dd></div>
                    <div><dt class="font-medium text-gray-800">Ubicacion</dt><dd>{{ $parsed['profile']['location'] ?? 'No detectado' }}</dd></div>
                    <div><dt class="font-medium text-gray-800">Headline</dt><dd>{{ $parsed['profile']['headline'] ?? 'No detectado' }}</dd></div>
                    <div><dt class="font-medium text-gray-800">LinkedIn</dt><dd>{{ $parsed['profile']['linkedin_url'] ?? 'No detectado' }}</dd></div>
                    <div><dt class="font-medium text-gray-800">Portfolio</dt><dd>{{ $parsed['profile']['portfolio_url'] ?? 'No detectado' }}</dd></div>
                </dl>
            </div>

            <div class="rounded border border-gray-200 p-4">
                <h4 class="font-semibold text-gray-900">Resumen</h4>
                <p class="mt-3 text-gray-600 whitespace-pre-line">{{ $parsed['profile']['summary'] ?? 'No detectado' }}</p>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div class="rounded border border-gray-200 p-4">
                <h4 class="font-semibold text-gray-900">Experiencia</h4>
                <ul class="mt-3 space-y-2 text-gray-600">
                    @forelse (($parsed['experiences'] ?? []) as $experience)
                        <li>{{ $experience['position'] ?? $experience['title'] ?? 'Puesto' }} / {{ $experience['company'] ?? $experience['organization'] ?? 'Empresa' }} / {{ $experience['period'] ?? 'Periodo por revisar' }}</li>
                    @empty
                        <li>No se detecto experiencia.</li>
                    @endforelse
                </ul>
            </div>
            <div class="rounded border border-gray-200 p-4">
                <h4 class="font-semibold text-gray-900">Educacion</h4>
                <ul class="mt-3 space-y-2 text-gray-600">
                    @forelse (($parsed['education'] ?? []) as $education)
                        <li>{{ $education['degree'] ?? $education['title'] ?? 'Estudio' }} / {{ $education['institution'] ?? $education['organization'] ?? 'Institucion' }} / {{ $education['period'] ?? 'Periodo por revisar' }}</li>
                    @empty
                        <li>No se detecto educacion.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div class="rounded border border-gray-200 p-4">
                <h4 class="font-semibold text-gray-900">Software</h4>
                <p class="mt-3 text-gray-600">{{ collect($parsed['software'] ?? [])->join('; ') ?: 'No se detecto software.' }}</p>
            </div>
            <div class="rounded border border-gray-200 p-4">
                <h4 class="font-semibold text-gray-900">Habilidades</h4>
                <p class="mt-3 text-gray-600">{{ collect($parsed['skills'] ?? [])->join('; ') ?: 'No se detectaron habilidades.' }}</p>
            </div>
            <div class="rounded border border-gray-200 p-4">
                <h4 class="font-semibold text-gray-900">Idiomas</h4>
                <p class="mt-3 text-gray-600">{{ collect($parsed['languages'] ?? [])->join('; ') ?: 'No se detectaron idiomas.' }}</p>
            </div>
            <div class="rounded border border-gray-200 p-4">
                <h4 class="font-semibold text-gray-900">Habilidades blandas</h4>
                <p class="mt-3 text-gray-600">{{ collect($parsed['soft_skills'] ?? [])->join('; ') ?: 'No se detectaron habilidades blandas.' }}</p>
            </div>
        </div>

        <div class="rounded border border-gray-200 p-4 text-sm">
            <h4 class="font-semibold text-gray-900">Certificaciones</h4>
            <p class="mt-3 text-gray-600 whitespace-pre-line">{{ collect($parsed['awards'] ?? [])->join("\n") ?: 'No se detectaron certificaciones.' }}</p>
        </div>

        @if (isset($applyAction))
            <form method="POST" action="{{ $applyAction }}" class="space-y-4">
                @csrf
                @include('cv._document_import_options')
                <button class="inline-flex h-10 items-center justify-center whitespace-nowrap rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800">Aplicar IA al CV</button>
            </form>
        @endif
    </section>
@endif
