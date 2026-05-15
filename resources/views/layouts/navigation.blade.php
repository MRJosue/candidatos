@php
    $availableThemes = \App\Models\ApplicationTheme::query()->where('is_active', true)->orderBy('name')->get();
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 transition-colors duration-200 dark:border-stone-800 dark:bg-stone-900">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-orange-50" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 lg:-my-px lg:ms-10 lg:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('talents.index')" :active="request()->routeIs('talents.*')">
                        Talentos
                    </x-nav-link>
                    <x-nav-link :href="route('cv.index')" :active="request()->routeIs('cv.*')">
                        CVs
                    </x-nav-link>
                    <x-nav-link :href="route('templates.index')" :active="request()->routeIs('templates.*')">
                        Plantillas
                    </x-nav-link>
                    <x-nav-link :href="route('companies.index')" :active="request()->routeIs('companies.*')">
                        Compañías
                    </x-nav-link>
                    <x-nav-link :href="route('vacancies.index')" :active="request()->routeIs('vacancies.*')">
                        Vacantes
                    </x-nav-link>
                    <x-nav-link :href="route('applications.index')" :active="request()->routeIs('applications.*')">
                        Postulaciones
                    </x-nav-link>
                    <x-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                        Citas
                    </x-nav-link>
                    @if (Auth::user()->hasAnyRole(['admin', 'administrator']))
                        <x-nav-link :href="route('admin.themes.index')" :active="request()->routeIs('admin.themes.*')">
                            Temas
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden lg:flex lg:items-center lg:ms-6">
                <button
                    type="button"
                    class="me-3 inline-flex h-9 w-9 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-stone-700 dark:text-orange-100 dark:hover:bg-stone-800 dark:hover:text-white"
                    x-on:click="$store.theme.toggle()"
                    x-bind:aria-label="$store.theme.isDark() ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                    x-bind:title="$store.theme.isDark() ? 'Modo claro' : 'Modo oscuro'"
                >
                    <svg x-show="! $store.theme.isDark()" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.5 6.5 0 0 0 9.8 9.8Z" />
                    </svg>
                    <svg x-cloak x-show="$store.theme.isDark()" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="4" />
                        <path stroke-linecap="round" d="M12 2.75v2M12 19.25v2M4.42 4.42l1.42 1.42M18.16 18.16l1.42 1.42M2.75 12h2M19.25 12h2M4.42 19.58l1.42-1.42M18.16 5.84l1.42-1.42" />
                    </svg>
                </button>

                <details class="relative">
                    <summary class="flex cursor-pointer list-none items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:text-orange-100 dark:hover:text-white">
                        <span>{{ Auth::user()->name }}</span>

                        <span class="ms-1">
                            <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </summary>

                    <div class="absolute end-0 z-50 mt-2 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-stone-900 dark:ring-amber-100/10">
                        <form method="POST" action="{{ route('profile.appearance.update') }}" class="border-b border-gray-100 px-4 py-3 dark:border-stone-800">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="theme_mode" value="{{ Auth::user()->theme_mode ?? 'system' }}">
                            <label for="nav_application_theme_id" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Tema</label>
                            <select
                                id="nav_application_theme_id"
                                name="application_theme_id"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                onchange="this.form.submit()"
                            >
                                @foreach ($availableThemes as $theme)
                                    <option value="{{ $theme->id }}" @selected((Auth::user()->application_theme_id ?? $applicationTheme?->id) == $theme->id)>
                                        {{ $theme->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </div>
                </details>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out dark:text-orange-100 dark:hover:bg-stone-800 dark:hover:text-white dark:focus:bg-stone-800 dark:focus:text-white">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('talents.index')" :active="request()->routeIs('talents.*')">
                Talentos
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('cv.index')" :active="request()->routeIs('cv.*')">
                CVs
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('templates.index')" :active="request()->routeIs('templates.*')">
                Plantillas
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('companies.index')" :active="request()->routeIs('companies.*')">
                Compañías
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('vacancies.index')" :active="request()->routeIs('vacancies.*')">
                Vacantes
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('applications.index')" :active="request()->routeIs('applications.*')">
                Postulaciones
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('appointments.index')" :active="request()->routeIs('appointments.*')">
                Citas
            </x-responsive-nav-link>
            @if (Auth::user()->hasAnyRole(['admin', 'administrator']))
                <x-responsive-nav-link :href="route('admin.themes.index')" :active="request()->routeIs('admin.themes.*')">
                    Temas
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-orange-50">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500 dark:text-stone-300">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <form method="POST" action="{{ route('profile.appearance.update') }}" class="px-4 py-2">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="theme_mode" value="{{ Auth::user()->theme_mode ?? 'system' }}">
                    <label for="responsive_application_theme_id" class="block text-sm font-medium text-gray-600 dark:text-orange-100">Tema</label>
                    <select
                        id="responsive_application_theme_id"
                        name="application_theme_id"
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        onchange="this.form.submit()"
                    >
                        @foreach ($availableThemes as $theme)
                            <option value="{{ $theme->id }}" @selected((Auth::user()->application_theme_id ?? $applicationTheme?->id) == $theme->id)>
                                {{ $theme->name }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <button
                    type="button"
                    class="flex w-full items-center gap-2 px-4 py-2 text-start text-base font-medium text-gray-600 transition hover:bg-gray-50 hover:text-gray-800 focus:outline-none dark:text-orange-100 dark:hover:bg-stone-800 dark:hover:text-white"
                    x-on:click="$store.theme.toggle()"
                >
                    <span x-text="$store.theme.isDark() ? 'Modo claro' : 'Modo oscuro'"></span>
                </button>

                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
