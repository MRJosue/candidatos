<x-app-layout>
    @php
        $skills = $profile->skills->where('type', 'skill');
        $languages = $profile->skills->where('type', 'language');
        $softSkills = $profile->skills->where('type', 'soft_skill');
        $skillsTitle = $profile->skills_section_title ?: 'Habilidades';
        $softSkillsTitle = $profile->soft_skills_section_title ?: 'Habilidades blandas';
        $sectionOrder = $profile->normalizedSectionOrder();
        $sideSectionLabels = [
            'skills' => $skillsTitle,
            'languages' => 'Idiomas',
            'soft_skills' => $softSkillsTitle,
        ];
        $mainSectionLabels = [
            'experiences' => 'Experiencia',
            'education' => 'Educacion',
        ];
        $sideSectionOrder = collect($sectionOrder['side'])
            ->filter(fn ($section) => array_key_exists($section, $sideSectionLabels))
            ->unique()
            ->merge(collect(array_keys($sideSectionLabels))->diff($sectionOrder['side']))
            ->values();
        $mainSectionOrder = collect($sectionOrder['main'])
            ->filter(fn ($section) => array_key_exists($section, $mainSectionLabels))
            ->unique()
            ->merge(collect(array_keys($mainSectionLabels))->diff($sectionOrder['main']))
            ->values();
        $previewHtml = view('cv.pdf', ['profile' => $profile])->render();
    @endphp
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <h2 class="font-semibold text-xl text-gray-800">{{ $profile->title }}</h2>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <form method="POST" action="{{ route('cv.template.update', $profile) }}" class="min-w-64">
                    @csrf
                    @method('PATCH')
                    <label class="block">
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Tipo de CV para imprimir</span>
                        <select name="cv_template_id" onchange="this.form.submit()" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Clasico profesional</option>
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
                    <button
                        type="button"
                        data-cv-preview-open
                        class="inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                    >
                        Vista previa
                    </button>
                    <a href="{{ route('cv.download', $profile) }}" class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <path d="M7 10l5 5 5-5" />
                            <path d="M12 15V3" />
                        </svg>
                        Descargar PDF
                    </a>
                    <a href="{{ route('cv.edit', $profile) }}" class="inline-flex items-center rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Editar</a>
                </div>
            </div>
        </div>
    </x-slot>

    <style>
        #cv-preview-dialog::backdrop {
            background: rgba(107, 114, 128, 0.75);
        }
    </style>

    <dialog
        id="cv-preview-dialog"
        class="overflow-hidden rounded-lg bg-white p-0 shadow-xl"
        style="width: 96vw; max-width: 96vw; height: 94vh; max-height: 94vh;"
    >
        <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Vista previa del CV</h3>
                <p class="text-sm text-gray-500">{{ $profile->template?->name ?? 'Clasico profesional'  }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('cv.download', $profile) }}" class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <path d="M7 10l5 5 5-5" />
                        <path d="M12 15V3" />
                    </svg>
                    Descargar
                </a>
                <button type="button" data-cv-preview-close class="rounded-md border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cerrar</button>
            </div>
        </div>
        <div class="bg-gray-100 p-3 sm:p-4" style="height: calc(94vh - 73px);">
            <iframe
                data-cv-preview-frame
                title="Vista previa amplia del CV"
                class="h-full w-full rounded border border-gray-200 bg-white shadow-sm"
            ></iframe>
        </div>
    </dialog>
    <script type="application/json" id="cv-preview-html">@json($previewHtml)</script>

    <div class="py-8"><div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))<div class="bg-emerald-50 text-emerald-800 p-4 rounded">{{ session('status') }}</div>@endif
        <section class="bg-white p-6 rounded shadow-sm">
            <h3 class="text-2xl font-semibold">{{ $profile->full_name }}</h3>
            <p class="text-gray-600">{{ $profile->headline }}</p>
            @if ($profile->tagline)<p class="text-gray-500 italic">{{ $profile->tagline }}</p>@endif
            <p class="mt-3">{{ $profile->summary }}</p>
            @if ($profile->objective)<p class="mt-3"><strong>Objetivo:</strong> {{ $profile->objective }}</p>@endif
            <p class="mt-3 text-sm text-gray-500">Plantilla: {{ $profile->template?->name ?? 'Sin plantilla' }}</p>
        </section>

        <div
            class="space-y-6"
            x-data="cvSectionOrder({
                side: @js($sideSectionOrder->all()),
                main: @js($mainSectionOrder->all()),
                url: '{{ route('cv.section-order.update', $profile) }}',
                csrf: '{{ csrf_token() }}'
            })"
            x-on:dragover.prevent
        >
            <div class="-mb-3 flex justify-end">
                <p class="text-sm" :class="hasError ? 'text-red-700' : 'text-emerald-700'" x-text="status"></p>
            </div>

            <section class="rounded border border-gray-200 bg-white/60 p-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Experiencia / Educacion</h3>
                    <span class="text-xs text-gray-400">Arrastra los cards para ordenar</span>
                </div>
                <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($mainSectionOrder as $section)
                @if ($section === 'experiences')
                    <section
                        class="bg-white p-6 rounded shadow-sm transition"
                        data-group="main"
                        data-section="experiences"
                        draggable="true"
                        x-bind:style="cardStyle('main', 'experiences')"
                        x-bind:class="cardClass('experiences')"
                        x-on:dragstart="dragStart('main', 'experiences', $event)"
                        x-on:dragend="dragEnd()"
                        x-on:drop.prevent="drop('main', 'experiences', $event)"
                    >
                        <div class="flex justify-between mb-3">
                            <h3 class="font-semibold cursor-move">Experiencia</h3>
                            <a href="{{ route('cv.experiences.create', $profile) }}" class="text-indigo-700">Agregar</a>
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
                        draggable="true"
                        x-bind:style="cardStyle('main', 'education')"
                        x-bind:class="cardClass('education')"
                        x-on:dragstart="dragStart('main', 'education', $event)"
                        x-on:dragend="dragEnd()"
                        x-on:drop.prevent="drop('main', 'education', $event)"
                    >
                        <div class="flex justify-between mb-3">
                            <h3 class="font-semibold cursor-move">Educacion</h3>
                            <a href="{{ route('cv.education.create', $profile) }}" class="text-indigo-700">Agregar</a>
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
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Habilidades / Idiomas / Habilidades blandas</h3>
                    <span class="text-xs text-gray-400">Arrastra los cards para ordenar</span>
                </div>
                <div class="grid gap-6 lg:grid-cols-3">
            @foreach ($sideSectionOrder as $section)
                @if ($section === 'skills')
                    <section
                        class="bg-white p-6 rounded shadow-sm transition"
                        data-group="side"
                        data-section="skills"
                        draggable="true"
                        x-bind:style="cardStyle('side', 'skills')"
                        x-bind:class="cardClass('skills')"
                        x-on:dragstart="dragStart('side', 'skills', $event)"
                        x-on:dragend="dragEnd()"
                        x-on:drop.prevent="drop('side', 'skills', $event)"
                    >
                        <div class="flex justify-between mb-3"><h3 class="font-semibold cursor-move">{{ $skillsTitle }}</h3><a href="{{ route('cv.skills.create', ['cvProfile' => $profile, 'type' => 'skill']) }}" class="text-indigo-700">Agregar</a></div>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($skills as $skill)
                                <span class="inline-flex items-center gap-2 rounded bg-gray-100 px-2 py-1">
                                    {{ $skill->name }}
                                    <a href="{{ route('skills.edit', $skill) }}" class="text-xs text-indigo-700">Editar</a>
                                </span>
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
                        draggable="true"
                        x-bind:style="cardStyle('side', 'languages')"
                        x-bind:class="cardClass('languages')"
                        x-on:dragstart="dragStart('side', 'languages', $event)"
                        x-on:dragend="dragEnd()"
                        x-on:drop.prevent="drop('side', 'languages', $event)"
                    >
                        <div class="flex justify-between mb-3"><h3 class="font-semibold cursor-move">Idiomas</h3><a href="{{ route('cv.skills.create', ['cvProfile' => $profile, 'type' => 'language']) }}" class="text-indigo-700">Agregar</a></div>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($languages as $skill)
                                <span class="inline-flex items-center gap-2 rounded bg-gray-100 px-2 py-1">
                                    {{ $skill->name }}@if($skill->level) · {{ $skill->level }}/5 @endif
                                    <a href="{{ route('skills.edit', $skill) }}" class="text-xs text-indigo-700">Editar</a>
                                </span>
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
                        draggable="true"
                        x-bind:style="cardStyle('side', 'soft_skills')"
                        x-bind:class="cardClass('soft_skills')"
                        x-on:dragstart="dragStart('side', 'soft_skills', $event)"
                        x-on:dragend="dragEnd()"
                        x-on:drop.prevent="drop('side', 'soft_skills', $event)"
                    >
                        <div class="flex justify-between mb-3"><h3 class="font-semibold cursor-move">{{ $softSkillsTitle }}</h3><a href="{{ route('cv.skills.create', ['cvProfile' => $profile, 'type' => 'soft_skill']) }}" class="text-indigo-700">Agregar</a></div>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($softSkills as $skill)
                                <span class="inline-flex items-center gap-2 rounded bg-gray-100 px-2 py-1">
                                    {{ $skill->name }}
                                    <a href="{{ route('skills.edit', $skill) }}" class="text-xs text-indigo-700">Editar</a>
                                </span>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dialog = document.getElementById('cv-preview-dialog');
            const openButton = document.querySelector('[data-cv-preview-open]');
            const closeButton = document.querySelector('[data-cv-preview-close]');
            const previewFrame = document.querySelector('[data-cv-preview-frame]');
            const previewHtmlSource = document.getElementById('cv-preview-html');

            const writePreview = () => {
                if (! previewFrame?.contentWindow || ! previewHtmlSource?.textContent) {
                    return;
                }

                const previewDocument = previewFrame.contentWindow.document;
                previewDocument.open();
                previewDocument.write(JSON.parse(previewHtmlSource.textContent));
                previewDocument.close();
            };

            openButton?.addEventListener('click', () => {
                if (dialog?.showModal) {
                    dialog.showModal();
                    writePreview();
                    document.body.classList.add('overflow-y-hidden');
                }
            });

            closeButton?.addEventListener('click', () => {
                dialog?.close();
            });

            dialog?.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    dialog.close();
                }
            });

            dialog?.addEventListener('close', () => {
                document.body.classList.remove('overflow-y-hidden');
            });
        });

        function cvSectionOrder(config) {
            return {
                side: config.side,
                main: config.main,
                status: '',
                hasError: false,
                dragging: null,
                dragStart(group, section, event) {
                    this.dragging = { group, section };
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', section);
                },
                dragEnd() {
                    this.dragging = null;
                },
                cardClass(section) {
                    return this.dragging?.section === section
                        ? 'opacity-60 ring-2 ring-indigo-200'
                        : 'hover:ring-2 hover:ring-indigo-100';
                },
                cardStyle(group, section) {
                    return `order: ${this[group].indexOf(section)}`;
                },
                drop(group, section) {
                    if (! this.dragging || this.dragging.group !== group || this.dragging.section === section) {
                        this.dragging = null;
                        return;
                    }

                    this[group] = this.moveSection(this[group], this.dragging.section, section);
                    this.dragging = null;
                    this.save();
                },
                moveSection(order, fromSection, toSection) {
                    const items = [...order];
                    const fromIndex = items.indexOf(fromSection);
                    const toIndex = items.indexOf(toSection);

                    if (fromIndex === -1 || toIndex === -1) {
                        return items;
                    }

                    const [moved] = items.splice(fromIndex, 1);
                    items.splice(toIndex, 0, moved);

                    return items;
                },
                normalizeOrder(order, allowed) {
                    return [
                        ...new Set(order.filter((section) => allowed.includes(section))),
                        ...allowed.filter((section) => ! order.includes(section)),
                    ];
                },
                async save() {
                    this.status = 'Guardando...';
                    this.hasError = false;

                    try {
                        const response = await fetch(config.url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': config.csrf,
                            },
                            body: JSON.stringify({ side: this.side, main: this.main }),
                        });

                        if (! response.ok) {
                            const error = await response.json().catch(() => null);
                            throw new Error(error?.message || 'No se pudo guardar el orden.');
                        }

                        this.status = 'Orden guardado.';
                    } catch (error) {
                        this.hasError = true;
                        this.status = error.message;
                    }
                },
            };
        }
    </script>
</x-app-layout>
