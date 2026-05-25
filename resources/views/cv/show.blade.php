<x-app-layout>
    @php
        $software = $profile->skills->where('type', 'software');
        $skills = $profile->skills->where('type', 'skill');
        $languages = $profile->skills->where('type', 'language');
        $softSkills = $profile->skills->where('type', 'soft_skill');
        $skillsTitle = $profile->skills_section_title ?: 'Habilidades';
        $softSkillsTitle = $profile->soft_skills_section_title ?: 'Habilidades blandas';
        $sideSectionLabels = [
            'software' => 'Software',
            'skills' => $skillsTitle,
            'languages' => 'Idiomas',
            'soft_skills' => $softSkillsTitle,
        ];
        $mainSectionLabels = [
            'experiences' => 'Experiencia',
            'education' => 'Educación',
        ];
        $sideSectionOrder = collect(array_keys($sideSectionLabels));
        $mainSectionOrder = collect(array_keys($mainSectionLabels));
    @endphp
    <x-slot name="header">
        <div x-data="{ translating: false }" class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">{{ $profile->title }}</h2>
                <p class="text-sm text-gray-500">Idioma: {{ $profile->languageLabel() }}</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <form method="POST" action="{{ route('cv.translate', $profile) }}" class="min-w-56" x-on:submit="translating = true">
                    @csrf
                    <label class="block">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Traducir CV</span>
                        <div class="mt-1 flex gap-2">
                            <select name="target_language" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($languageOptions as $language => $label)
                                    <option value="{{ $language }}" @disabled(($profile->language ?: 'es') === $language) @selected(($profile->language ?: 'es') !== $language)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-wait disabled:bg-indigo-400" x-bind:disabled="translating">
                                <span x-show="! translating">Crear</span>
                                <span x-cloak x-show="translating">Creando</span>
                            </button>
                        </div>
                    </label>
                </form>
                <form method="POST" action="{{ route('cv.template.update', $profile) }}" class="min-w-64">
                    @csrf
                    @method('PATCH')
                    <label class="block">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Tipo de CV para imprimir</span>
                        <select name="cv_template_id" onchange="this.form.submit()" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($templates as $template)
                                @php
                                    $canUseTemplate = ! $template->is_premium || in_array($template->id, $purchasedTemplateIds, true) || $profile->cv_template_id === $template->id;
                                @endphp
                                <option value="{{ $template->id }}" @selected($profile->cv_template_id === $template->id) @disabled(! $canUseTemplate)>
                                    {{ $template->name }}{{ $template->is_premium ? ($canUseTemplate ? ' - Premium' : ' - Premium bloqueada') : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </form>

                <div class="flex items-center gap-2">
                    <form method="GET" action="{{ route('cv.download', $profile) }}" class="flex items-center gap-2">
                        <select name="language" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($languageOptions as $language => $label)
                                <option value="{{ $language }}" @selected(($profile->language ?: 'es') === $language)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <path d="M7 10l5 5 5-5" />
                                <path d="M12 15V3" />
                            </svg>
                            Descargar PDF
                        </button>
                    </form>
                </div>
            </div>

            <div
                x-cloak
                x-show="translating"
                x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/70 px-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="translation-loading-title"
            >
                <div class="w-full max-w-sm rounded-lg bg-white p-6 text-center shadow-xl">
                    <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></div>
                    <h3 id="translation-loading-title" class="mt-4 text-lg font-semibold text-gray-900">Creando CV traducido</h3>
                    <p class="mt-2 text-sm text-gray-500">Esto puede tomar unos segundos.</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8"><div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))<div class="bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="bg-red-50 text-red-700 p-4 rounded">{{ $errors->first() }}</div>@endif
        <section class="bg-white p-6 rounded shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-2xl font-semibold">{{ $profile->full_name }}</h3>
                    <p class="text-gray-600">{{ $profile->headline }}</p>
                    @if ($profile->tagline)<p class="text-gray-500 italic">{{ $profile->tagline }}</p>@endif
                    <p class="mt-3">{{ $profile->summary }}</p>
                    @if ($profile->objective)<p class="mt-3"><strong>Objetivo:</strong> {{ $profile->objective }}</p>@endif
                    <p class="mt-3 text-sm text-gray-500">Plantilla: {{ $profile->template?->name ?? 'Sin plantilla' }}</p>
                    @if ($profile->sourceCvProfile)
                        <p class="mt-1 text-sm text-gray-500">Traducido desde: <a href="{{ route('cv.show', $profile->sourceCvProfile) }}" class="text-indigo-700">{{ $profile->sourceCvProfile->title }}</a></p>
                    @endif
                    @if ($profile->translations->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2 text-sm">
                            @foreach ($profile->translations as $translation)
                                <a href="{{ route('cv.show', $translation) }}" class="rounded bg-indigo-50 px-2 py-1 text-indigo-700">
                                    {{ $translation->languageLabel() }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                <a href="{{ route('cv.edit', $profile) }}" class="inline-flex items-center self-start rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Editar desde formulario</a>
            </div>
        </section>

        <div class="space-y-6">
            <section class="rounded border border-gray-200 bg-white/60 p-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Experiencia / Educación</h3>
                </div>
                <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($mainSectionOrder as $section)
                @if ($section === 'experiences')
                    <section
                        class="bg-white p-6 rounded shadow-sm transition"
                        data-group="main"
                        data-section="experiences"
                    >
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="font-semibold">Experiencia</h3>
                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <form method="POST" action="{{ route('cv.experiences.reverse-order', $profile) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-indigo-700">Invertir orden</button>
                                </form>
                                <a href="{{ route('cv.experiences.create', $profile) }}" class="text-indigo-700">Agregar</a>
                            </div>
                        </div>
                        <div class="space-y-4">
                            @forelse ($profile->experiences as $item)
                                <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <p>
                                            <strong>{{ $item->position }}</strong><br>
                                            <span class="text-gray-700">{{ $item->company }}</span>
                                        </p>
                                        <div class="flex items-center gap-2 text-sm">
                                            <a href="{{ route('experiences.edit', $item) }}" class="text-indigo-700">Editar</a>
                                            <form method="POST" action="{{ route('experiences.destroy', $item) }}" onsubmit="return confirm('¿Eliminar esta experiencia?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-700">Eliminar</button>
                                            </form>
                                        </div>
                                    </div>
                                    @if ($item->location)
                                        <p class="text-sm text-gray-500">{{ $item->location }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Aun no has agregado experiencia.</p>
                            @endforelse
                        </div>
                    </section>
                @elseif ($section === 'education')
                    <section
                        class="bg-white p-6 rounded shadow-sm transition"
                        data-group="main"
                        data-section="education"
                    >
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="font-semibold">Educación</h3>
                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <form method="POST" action="{{ route('cv.education.reverse-order', $profile) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-indigo-700">Invertir orden</button>
                                </form>
                                <a href="{{ route('cv.education.create', $profile) }}" class="text-indigo-700">Agregar</a>
                            </div>
                        </div>
                        <div class="space-y-4">
                            @forelse ($profile->education as $item)
                                <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <p>
                                            <strong>{{ $item->degree }}</strong><br>
                                            <span class="text-gray-700">{{ $item->institution }}</span>
                                        </p>
                                        <div class="flex items-center gap-2 text-sm">
                                            <a href="{{ route('education.edit', $item) }}" class="text-indigo-700">Editar</a>
                                            <form method="POST" action="{{ route('education.destroy', $item) }}" onsubmit="return confirm('¿Eliminar esta educacion?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-700">Eliminar</button>
                                            </form>
                                        </div>
                                    </div>
                                    @if ($item->field)
                                        <p class="text-sm text-gray-500">{{ $item->field }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Aun no has agregado educacion.</p>
                            @endforelse
                        </div>
                    </section>
                @endif
            @endforeach
                </div>
            </section>

            <section class="rounded border border-gray-200 bg-white/60 p-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Software / Habilidades / Idiomas / Habilidades blandas</h3>
                </div>
                <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-4">
            @foreach ($sideSectionOrder as $section)
                @if ($section === 'software')
                    <section
                        class="bg-white p-6 rounded shadow-sm transition"
                        data-group="side"
                        data-section="software"
                    >
                        <div class="flex justify-between mb-3"><h3 class="font-semibold">Software</h3><a href="{{ route('cv.skills.create', ['cvProfile' => $profile, 'type' => 'software']) }}" class="text-indigo-700">Agregar</a></div>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($software as $skill)
                                <div class="inline-flex items-center gap-2 rounded bg-gray-100 px-2 py-1">
                                    {{ $skill->name }}
                                    <a href="{{ route('skills.edit', $skill) }}" class="text-xs text-indigo-700">Editar</a>
                                    <form method="POST" action="{{ route('skills.destroy', $skill) }}" onsubmit="return confirm('¿Eliminar este software?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-700">Eliminar</button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Aun no has agregado software.</p>
                            @endforelse
                        </div>
                    </section>
                @elseif ($section === 'skills')
                    <section
                        class="bg-white p-6 rounded shadow-sm transition"
                        data-group="side"
                        data-section="skills"
                    >
                        <div class="flex justify-between mb-3"><h3 class="font-semibold">{{ $skillsTitle }}</h3><a href="{{ route('cv.skills.create', ['cvProfile' => $profile, 'type' => 'skill']) }}" class="text-indigo-700">Agregar</a></div>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($skills as $skill)
                                <div class="inline-flex items-center gap-2 rounded bg-gray-100 px-2 py-1">
                                    {{ $skill->name }}
                                    <a href="{{ route('skills.edit', $skill) }}" class="text-xs text-indigo-700">Editar</a>
                                    <form method="POST" action="{{ route('skills.destroy', $skill) }}" onsubmit="return confirm('¿Eliminar esta habilidad?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-700">Eliminar</button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Aun no has agregado habilidades.</p>
                            @endforelse
                        </div>
                    </section>
                @elseif ($section === 'languages')
                    <section
                        class="bg-white p-6 rounded shadow-sm transition"
                        data-group="side"
                        data-section="languages"
                    >
                        <div class="flex justify-between mb-3"><h3 class="font-semibold">Idiomas</h3><a href="{{ route('cv.skills.create', ['cvProfile' => $profile, 'type' => 'language']) }}" class="text-indigo-700">Agregar</a></div>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($languages as $skill)
                                <div class="inline-flex items-center gap-2 rounded bg-gray-100 px-2 py-1">
                                    {{ $skill->name }}@if($skill->level) · {{ $skill->level }}/5 @endif
                                    <a href="{{ route('skills.edit', $skill) }}" class="text-xs text-indigo-700">Editar</a>
                                    <form method="POST" action="{{ route('skills.destroy', $skill) }}" onsubmit="return confirm('¿Eliminar este idioma?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-700">Eliminar</button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Aun no has agregado idiomas.</p>
                            @endforelse
                        </div>
                    </section>
                @elseif ($section === 'soft_skills')
                    <section
                        class="bg-white p-6 rounded shadow-sm transition"
                        data-group="side"
                        data-section="soft_skills"
                    >
                        <div class="flex justify-between mb-3"><h3 class="font-semibold">{{ $softSkillsTitle }}</h3><a href="{{ route('cv.skills.create', ['cvProfile' => $profile, 'type' => 'soft_skill']) }}" class="text-indigo-700">Agregar</a></div>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($softSkills as $skill)
                                <div class="inline-flex items-center gap-2 rounded bg-gray-100 px-2 py-1">
                                    {{ $skill->name }}
                                    <a href="{{ route('skills.edit', $skill) }}" class="text-xs text-indigo-700">Editar</a>
                                    <form method="POST" action="{{ route('skills.destroy', $skill) }}" onsubmit="return confirm('¿Eliminar esta habilidad blanda?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-700">Eliminar</button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Aun no has agregado habilidades blandas.</p>
                            @endforelse
                        </div>
                    </section>
                @endif
            @endforeach
                </div>
            </section>
        </div>
    </div></div>
</x-app-layout>
