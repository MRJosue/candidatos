@php
    $lightPalette = $theme->paletteFor('light');
    $darkPalette = $theme->paletteFor('dark');
@endphp

@csrf

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
    <div class="space-y-6">
        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Nombre" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $theme->name)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="slug" value="Slug" />
                    <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug', $theme->slug)" />
                    <x-input-error class="mt-2" :messages="$errors->get('slug')" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="description" value="Descripción" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">{{ old('description', $theme->description) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            @foreach (['light_palette' => ['title' => 'Tema claro', 'palette' => $lightPalette], 'dark_palette' => ['title' => 'Tema oscuro', 'palette' => $darkPalette]] as $field => $group)
                <section class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">{{ $group['title'] }}</h3>
                    <div class="mt-5 grid gap-4">
                        @foreach (\App\Models\ApplicationTheme::TOKENS as $token)
                            <div class="grid grid-cols-[1fr_96px] items-center gap-3">
                                <x-input-label :for="$field.'_'.$token" :value="$token" />
                                <input
                                    id="{{ $field }}_{{ $token }}"
                                    name="{{ $field }}[{{ $token }}]"
                                    type="color"
                                    value="{{ old($field.'.'.$token, $group['palette'][$token]) }}"
                                    class="h-10 w-24 rounded-md border border-gray-300 bg-white p-1"
                                    required
                                >
                                <x-input-error class="col-span-2" :messages="$errors->get($field.'.'.$token)" />
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <aside class="space-y-6">
        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <h3 class="text-lg font-medium text-gray-900">Publicación</h3>

            <div class="mt-5 space-y-4">
                <label class="flex items-center gap-3 text-sm text-gray-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $theme->is_active)) class="rounded border-gray-300 text-amber-700 shadow-sm focus:ring-amber-500">
                    Disponible para usuarios
                </label>

                <label class="flex items-center gap-3 text-sm text-gray-700">
                    <input type="hidden" name="is_default" value="0">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $theme->is_default)) class="rounded border-gray-300 text-amber-700 shadow-sm focus:ring-amber-500">
                    Tema predeterminado
                </label>
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <h3 class="text-lg font-medium text-gray-900">Background</h3>

            @if ($theme->backgroundImageUrl())
                <img src="{{ $theme->backgroundImageUrl() }}" alt="" class="mt-4 aspect-video w-full rounded-md object-cover">
                <label class="mt-4 flex items-center gap-3 text-sm text-gray-700">
                    <input type="checkbox" name="remove_background" value="1" class="rounded border-gray-300 text-amber-700 shadow-sm focus:ring-amber-500">
                    Quitar imagen actual
                </label>
            @endif

            <input type="file" name="background_image" accept="image/*" class="mt-4 block w-full text-sm text-gray-600 file:me-4 file:rounded-md file:border-0 file:bg-amber-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-amber-800">
            <x-input-error class="mt-2" :messages="$errors->get('background_image')" />
        </div>

        <div class="flex items-center gap-3">
            <x-primary-button>Guardar tema</x-primary-button>
            <a href="{{ route('admin.themes.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Cancelar</a>
        </div>
    </aside>
</div>
