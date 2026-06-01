<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">CVs por talento</h2>
            <a href="{{ route('cv.create') }}" class="px-4 py-2 bg-gray-900 text-white rounded text-sm">Nuevo CV</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 p-4 rounded mb-4">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 text-red-700 p-4 rounded mb-4">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white rounded shadow-sm overflow-x-auto">
                <table class="min-w-[1040px] w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Talento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CV</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Idioma</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plantilla</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actualizado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase w-64">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($profilesByTalent as $talentKey => $group)
                            @php
                                $talent = $group->first()->talent;
                                $rowspan = $group->count();
                            @endphp

                            @foreach ($group as $profileIndex => $profile)
                                @php
                                    $canUpdateProfile = auth()->user()->can('update', $profile);
                                    $canDeleteProfile = auth()->user()->can('delete', $profile);
                                @endphp
                                <tr>
                                    @if ($profileIndex === 0)
                                        <td class="px-6 py-4 align-top" rowspan="{{ $rowspan }}">
                                            @if ($talent)
                                                <a href="{{ route('talents.show', $talent) }}" class="font-medium text-indigo-600">{{ $talent->full_name }}</a>
                                                <p class="text-sm text-gray-500">{{ $talent->email ?: $profile->email ?: 'Sin correo' }}</p>
                                                <p class="mt-1 text-xs text-gray-400">{{ $rowspan }} CV(s)</p>
                                            @else
                                                <span class="font-medium text-gray-900">Sin asignar</span>
                                                <p class="text-sm text-gray-500">CVs pendientes de vincular a un talento.</p>
                                                <p class="mt-1 text-xs text-gray-400">{{ $rowspan }} CV(s)</p>
                                            @endif
                                        </td>
                                    @endif

                                    <td class="px-6 py-4">
                                        <a href="{{ route('cv.show', $profile) }}" class="font-medium text-indigo-600">{{ $profile->title }}</a>
                                        <p class="text-sm text-gray-500">{{ $profile->full_name ?: 'Sin nombre detectado' }}</p>
                                        @if ($profile->user_id !== auth()->id())
                                            <p class="text-xs text-gray-400">Propietario: {{ $profile->user?->name ?? 'Usuario' }}</p>
                                        @endif
                                        @if ($profile->is_primary)
                                            <span class="mt-1 inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">Principal</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $profile->languageLabel() }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $profile->template?->name ?? 'Sin plantilla' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $profile->updated_at?->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                        <div
                                            class="inline-block"
                                            x-data="{
                                                open: false,
                                                top: 0,
                                                left: 0,
                                                place() {
                                                    const rect = this.$refs.trigger.getBoundingClientRect();
                                                    this.top = rect.bottom + 8;
                                                    this.left = Math.max(12, rect.right - 224);
                                                },
                                                toggle() {
                                                    this.open = ! this.open;
                                                    if (this.open) this.$nextTick(() => this.place());
                                                }
                                            }"
                                            x-on:keydown.escape.window="open = false"
                                            x-on:resize.window="if (open) place()"
                                            x-on:scroll.window="if (open) place()"
                                        >
                                            <button
                                                type="button"
                                                x-ref="trigger"
                                                x-on:click="toggle()"
                                                class="inline-flex items-center rounded border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                                                aria-haspopup="menu"
                                                x-bind:aria-expanded="open.toString()"
                                            >
                                                Acciones
                                                <svg class="ml-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <div
                                                x-cloak
                                                x-show="open"
                                                x-transition
                                                x-on:click.outside="open = false"
                                                x-bind:style="`top: ${top}px; left: ${left}px;`"
                                                class="fixed z-50 w-56 rounded-md bg-white py-1 text-left shadow-lg ring-1 ring-black ring-opacity-5"
                                                role="menu"
                                            >
                                                <a href="{{ route('cv.show', $profile) }}" class="app-dropdown-link block w-full px-4 py-2 text-start text-sm leading-5 transition duration-150 ease-in-out" role="menuitem">Abrir CV</a>
                                                <a href="{{ route('cv.download', $profile) }}" class="app-dropdown-link block w-full px-4 py-2 text-start text-sm leading-5 transition duration-150 ease-in-out" role="menuitem">Descargar PDF</a>
                                                <a href="{{ route('cv.download-word', $profile) }}" class="app-dropdown-link block w-full px-4 py-2 text-start text-sm leading-5 transition duration-150 ease-in-out" role="menuitem">Descargar Word</a>
                                                @if ($canUpdateProfile)
                                                    <button type="button" onclick="document.getElementById('assign-cv-{{ $profile->id }}').showModal()" class="app-dropdown-link block w-full px-4 py-2 text-start text-sm leading-5 transition duration-150 ease-in-out" role="menuitem">Asignar talento</button>
                                                @endif
                                                @if ($canDeleteProfile)
                                                    <form method="POST" action="{{ route('cv.destroy', $profile) }}" onsubmit="return confirm('¿Eliminar este CV? Esta acción no se puede deshacer.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="app-dropdown-link block w-full px-4 py-2 text-start text-sm leading-5 text-red-600 transition duration-150 ease-in-out" role="menuitem">Eliminar</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($canUpdateProfile)
                                            <dialog id="assign-cv-{{ $profile->id }}" class="w-full max-w-lg rounded-lg p-0 text-left shadow-xl backdrop:bg-gray-500/75">
                                                <form method="POST" action="{{ route('cv.talent.update', $profile) }}" class="p-6 space-y-5">
                                                    @csrf
                                                    @method('PATCH')

                                                    <div>
                                                        <h3 class="text-lg font-semibold text-gray-900">Asignar talento</h3>
                                                        <p class="mt-1 text-sm text-gray-500">{{ $profile->title }}</p>
                                                    </div>

                                                    <label class="block">
                                                        <span class="text-sm font-medium text-gray-700">Talento</span>
                                                        <select name="talent_id" class="mt-1 w-full rounded border-gray-300 text-sm">
                                                            <option value="">Sin asignar</option>
                                                            @foreach ($talents as $talentOption)
                                                                @php
                                                                    $cvCountExcludingCurrent = $talentOption->cvProfiles->reject(fn ($cvProfile) => $cvProfile->id === $profile->id)->count();
                                                                @endphp
                                                                <option
                                                                    value="{{ $talentOption->id }}"
                                                                    @selected($profile->talent_id === $talentOption->id)
                                                                    @disabled($cvCountExcludingCurrent >= \App\Models\CvProfile::MAX_PER_TALENT)
                                                                >
                                                                    {{ $talentOption->full_name }}
                                                                    @if ($talentOption->cvProfiles->isNotEmpty())
                                                                        - {{ $talentOption->cvProfiles->count() }} CV(s)
                                                                    @endif
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </label>

                                                    <div class="flex justify-end gap-3">
                                                        <button type="button" onclick="document.getElementById('assign-cv-{{ $profile->id }}').close()" class="rounded bg-gray-100 px-4 py-2 text-sm text-gray-700">Cancelar</button>
                                                        <button class="rounded bg-gray-900 px-4 py-2 text-sm font-medium text-white">Guardar</button>
                                                    </div>
                                                </form>
                                            </dialog>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">Crea tu primer CV para empezar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
