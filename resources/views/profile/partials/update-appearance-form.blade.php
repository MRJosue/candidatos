<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Apariencia
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Selecciona el tema visual y si quieres usar modo claro, oscuro o el modo del sistema.
        </p>
    </header>

    <form method="post" action="{{ route('profile.appearance.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="application_theme_id" value="Tema" />
            <select id="application_theme_id" name="application_theme_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                @foreach ($themes as $theme)
                    <option value="{{ $theme->id }}" @selected(old('application_theme_id', $user->application_theme_id ?? \App\Models\ApplicationTheme::default()->id) == $theme->id)>
                        {{ $theme->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('application_theme_id')" />
        </div>

        <div>
            <x-input-label for="theme_mode" value="Modo" />
            <select id="theme_mode" name="theme_mode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                <option value="system" @selected(old('theme_mode', $user->theme_mode) === 'system')>Sistema</option>
                <option value="light" @selected(old('theme_mode', $user->theme_mode) === 'light')>Claro</option>
                <option value="dark" @selected(old('theme_mode', $user->theme_mode) === 'dark')>Oscuro</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('theme_mode')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar apariencia</x-primary-button>

            @if (session('status') === 'appearance-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >Apariencia guardada.</p>
            @endif
        </div>
    </form>
</section>
