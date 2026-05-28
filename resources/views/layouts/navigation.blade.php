@php
    $availableThemes = \App\Models\ApplicationTheme::query()->where('is_active', true)->orderBy('name')->get();
@endphp

<nav x-data="{ open: false }" class="app-nav border-b transition-colors duration-200">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="app-logo block h-9 w-9" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-2 lg:-my-px lg:ms-8 lg:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('talents.index')" :active="request()->routeIs('talents.*')">
                        Talentos
                    </x-nav-link>
                    <x-nav-link :href="route('cv.index')" :active="request()->routeIs('cv.*')">
                        CVs
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
                    <div class="relative flex h-full items-stretch" x-data="{ businessMenuOpen: false }" x-on:click.outside="businessMenuOpen = false" x-on:keydown.escape.window="businessMenuOpen = false">
                        <div class="flex h-full items-stretch">
                            <button
                                type="button"
                                class="{{ request()->routeIs('usage.*') || request()->routeIs('admin.usage-subscriptions.*') || request()->routeIs('admin.users.*') || request()->routeIs('admin.themes.*') ? 'app-nav-link app-nav-link-active' : 'app-nav-link' }} inline-flex h-full items-center gap-1 border-b-2 px-3 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
                                x-on:click="businessMenuOpen = ! businessMenuOpen"
                                x-bind:aria-expanded="businessMenuOpen.toString()"
                            >
                                <span>Negocio</span>
                                <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div
                            x-cloak
                            x-show="businessMenuOpen"
                            x-transition.origin.top.left
                            class="app-user-dropdown absolute start-0 top-full mt-2 w-56 rounded-lg py-1 ring-1"
                        >
                            <x-dropdown-link :href="route('usage.index')">
                                Uso mensual
                            </x-dropdown-link>

                            @if (Auth::user()->hasAnyRole(['admin', 'administrator']))
                                <x-dropdown-link :href="route('admin.users.index')">
                                    Usuarios
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.usage-subscriptions.index')">
                                    Planes de cuentas
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('admin.themes.index')">
                                    Temas
                                </x-dropdown-link>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden lg:flex lg:items-center lg:ms-6">
                <button
                    type="button"
                    class="app-icon-button me-3 inline-flex h-9 w-9 items-center justify-center rounded-md border transition focus:outline-none focus:ring-2"
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

                <div class="relative" x-data="{ userMenuOpen: false }" x-on:click.outside="userMenuOpen = false" x-on:keydown.escape.window="userMenuOpen = false">
                    <button
                        type="button"
                        class="app-user-menu-trigger flex cursor-pointer list-none items-center rounded-md px-3 py-2 text-sm font-medium leading-4 transition duration-150 ease-in-out focus:outline-none focus:ring-2"
                        x-on:click="userMenuOpen = ! userMenuOpen"
                        x-bind:aria-expanded="userMenuOpen.toString()"
                    >
                        <span>{{ Auth::user()->name }}</span>

                        <span class="ms-1">
                            <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </button>

                    <div
                        x-cloak
                        x-show="userMenuOpen"
                        x-transition.origin.top.right
                        class="app-user-dropdown absolute end-0 mt-3 w-64 rounded-lg py-1 ring-1"
                    >
                        <form method="POST" action="{{ route('profile.appearance.update') }}" class="app-user-dropdown-section border-b px-4 py-3">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="theme_mode" value="{{ Auth::user()->theme_mode ?? 'system' }}">
                            <label for="nav_application_theme_id" class="app-menu-label block text-xs font-semibold uppercase tracking-wide">Tema</label>
                            <select
                                id="nav_application_theme_id"
                                name="application_theme_id"
                                class="app-menu-select mt-1 block w-full rounded-md border text-sm shadow-sm focus:ring-2"
                                onchange="this.form.submit()"
                            >
                                @foreach ($availableThemes as $theme)
                                    <option value="{{ $theme->id }}" @selected((Auth::user()->application_theme_id ?? $applicationTheme?->id) == $theme->id)>
                                        {{ $theme->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        <x-dropdown-link :href="route('pricing')">
                            Precios
                        </x-dropdown-link>

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
                </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="app-icon-button inline-flex items-center justify-center rounded-md border p-2 transition duration-150 ease-in-out focus:outline-none focus:ring-2">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="app-responsive-menu hidden lg:hidden">
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
            <div class="px-3 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
                Negocio
            </div>
            <x-responsive-nav-link :href="route('usage.index')" :active="request()->routeIs('usage.*')">
                Uso mensual
            </x-responsive-nav-link>
            @if (Auth::user()->hasAnyRole(['admin', 'administrator']))
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    Usuarios
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.usage-subscriptions.index')" :active="request()->routeIs('admin.usage-subscriptions.*')">
                    Planes de cuentas
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.themes.index')" :active="request()->routeIs('admin.themes.*')">
                    Temas
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="app-responsive-settings pt-4 pb-1 border-t">
            <div class="px-4">
                <div class="app-user-name font-medium text-base">{{ Auth::user()->name }}</div>
                <div class="app-user-email font-medium text-sm">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <form method="POST" action="{{ route('profile.appearance.update') }}" class="px-4 py-2">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="theme_mode" value="{{ Auth::user()->theme_mode ?? 'system' }}">
                    <label for="responsive_application_theme_id" class="app-menu-label block text-sm font-medium">Tema</label>
                    <select
                        id="responsive_application_theme_id"
                        name="application_theme_id"
                        class="app-menu-select mt-1 block w-full rounded-md border text-sm shadow-sm focus:ring-2"
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
                    class="app-responsive-menu-button flex w-full items-center gap-2 px-4 py-2 text-start text-base font-medium transition focus:outline-none"
                    x-on:click="$store.theme.toggle()"
                >
                    <span x-text="$store.theme.isDark() ? 'Modo claro' : 'Modo oscuro'"></span>
                </button>

                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('pricing')" :active="request()->routeIs('pricing')">
                    Precios
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
