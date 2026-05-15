<!DOCTYPE html>
@php
    $applicationTheme = Auth::user()?->applicationTheme ?? \App\Models\ApplicationTheme::default();
    $appearanceMode = Auth::user()?->theme_mode ?? 'system';
    $backgroundImageUrl = $applicationTheme->backgroundImageUrl();
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-app-theme="{{ $applicationTheme->slug }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>{{ config('app.name', 'CV Studio') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script>
            window.appAppearance = {
                mode: @json($appearanceMode),
                theme: @json($applicationTheme->slug),
            };

            (() => {
                const preferredMode = window.appAppearance.mode ?? 'system';
                const theme = preferredMode === 'system' ? localStorage.getItem('theme') : preferredMode;
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const useDark = theme === 'dark' || (! theme && prefersDark);

                document.documentElement.classList.toggle('dark', useDark);
                document.documentElement.style.colorScheme = useDark ? 'dark' : 'light';
            })();
        </script>
        @wireUiScripts
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root[data-app-theme="{{ $applicationTheme->slug }}"] {
                @foreach ($applicationTheme->paletteFor('light') as $token => $value)
                    --cv-{{ $token }}: {{ $value }};
                @endforeach
                --cv-bg-image: {{ $backgroundImageUrl ? "url('".$backgroundImageUrl."')" : 'none' }};
            }

            html.dark[data-app-theme="{{ $applicationTheme->slug }}"] {
                @foreach ($applicationTheme->paletteFor('dark') as $token => $value)
                    --cv-{{ $token }}: {{ $value }};
                @endforeach
            }
        </style>

        <style>
            .auth-page {
                min-height: 100vh;
                background:
                    linear-gradient(color-mix(in srgb, var(--cv-bg) 88%, transparent), color-mix(in srgb, var(--cv-bg) 92%, transparent)),
                    var(--cv-bg-image),
                    radial-gradient(circle at top left, var(--cv-surface-soft) 0, var(--cv-bg) 38%, var(--cv-surface-muted) 100%);
                background-position: center;
                background-size: cover;
                transition: background-color 150ms ease;
            }

            .auth-shell {
                display: grid;
                min-height: 100vh;
                align-items: center;
                justify-content: center;
                gap: 2rem;
                padding: 2rem 1.25rem;
            }

            .auth-panel {
                position: relative;
                display: none;
                min-height: 560px;
                width: min(420px, 100%);
                overflow: hidden;
                border-radius: 8px;
                background: linear-gradient(160deg, var(--cv-text) 0%, color-mix(in srgb, var(--cv-bg) 80%, #000 20%) 58%, var(--cv-accent-hover) 100%);
                color: var(--cv-surface);
                padding: 2rem;
                box-shadow: 0 24px 60px rgba(92, 52, 18, 0.22);
            }

            .auth-panel::before {
                content: "";
                position: absolute;
                inset: 0 0 auto;
                height: 4px;
                background: linear-gradient(90deg, var(--cv-accent-hover), var(--cv-accent), color-mix(in srgb, var(--cv-accent) 70%, #fff 30%));
            }

            .auth-panel-inner {
                display: flex;
                min-height: 496px;
                flex-direction: column;
                justify-content: space-between;
            }

            .auth-brand {
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 700;
            }

            .auth-mark {
                display: grid;
                width: 40px;
                height: 40px;
                place-items: center;
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.1);
                color: var(--cv-surface);
            }

            .auth-mark svg {
                width: 20px;
                height: 20px;
            }

            .auth-panel-copy {
                max-width: 350px;
            }

            .auth-panel-kicker {
                margin: 0 0 0.75rem;
                color: var(--cv-accent);
                font-size: 0.75rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .auth-panel-title {
                margin: 0;
                font-size: 2.25rem;
                font-weight: 700;
                line-height: 1.04;
            }

            .auth-panel-text {
                margin: 1rem 0 0;
                color: color-mix(in srgb, var(--cv-surface) 78%, transparent);
                font-size: 0.875rem;
                line-height: 1.65;
            }

            .auth-panel-list {
                display: grid;
                gap: 0.75rem;
                color: color-mix(in srgb, var(--cv-surface) 78%, transparent);
                font-size: 0.875rem;
            }

            .auth-panel-list span {
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
            }

            .auth-dot {
                width: 10px;
                height: 10px;
                border-radius: 999px;
                flex: 0 0 auto;
            }

            .auth-dot.green {
                background: var(--cv-accent-hover);
            }

            .auth-dot.gold {
                background: var(--cv-accent);
            }

            .auth-dot.blue {
                background: color-mix(in srgb, var(--cv-accent) 72%, var(--cv-text) 28%);
            }

            .auth-main {
                display: flex;
                width: min(390px, 100%);
                align-items: center;
                justify-content: center;
            }

            .auth-card-wrap {
                width: 100%;
            }

            .auth-mobile-brand {
                display: flex;
                justify-content: center;
                margin-bottom: 1.25rem;
            }

            .auth-mobile-brand a {
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                color: var(--cv-text);
                font-weight: 700;
            }

            .auth-mobile-brand .auth-mark {
                border-color: var(--cv-border);
                background: var(--cv-surface);
                color: var(--cv-accent);
                box-shadow: 0 1px 2px rgba(120, 72, 28, 0.1);
            }

            .auth-card {
                border: 1px solid var(--cv-border);
                border-radius: 8px;
                background: var(--cv-surface);
                padding: 1.5rem;
                box-shadow: 0 20px 45px rgba(120, 72, 28, 0.14);
            }

            .auth-theme-toggle {
                position: fixed;
                top: 1rem;
                right: 1rem;
                z-index: 20;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                border: 1px solid var(--cv-border);
                border-radius: 8px;
                background: var(--cv-surface);
                color: var(--cv-text-muted);
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
                font-weight: 600;
                box-shadow: 0 8px 24px rgba(120, 72, 28, 0.12);
                transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
            }

            .auth-theme-toggle:hover {
                background: var(--cv-surface-soft);
                color: var(--cv-text);
            }

            .dark .auth-page {
                background:
                    linear-gradient(color-mix(in srgb, var(--cv-bg) 88%, transparent), color-mix(in srgb, var(--cv-bg) 92%, transparent)),
                    var(--cv-bg-image),
                    radial-gradient(circle at top left, var(--cv-surface-soft) 0, var(--cv-bg) 45%, #0f0a07 100%);
                background-position: center;
                background-size: cover;
            }

            .dark .auth-mobile-brand a {
                color: var(--cv-text);
            }

            .dark .auth-mobile-brand .auth-mark,
            .dark .auth-card {
                border-color: var(--cv-border);
                background: var(--cv-surface);
                color: var(--cv-text);
            }

            .dark .auth-card {
                box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
            }

            .dark .auth-theme-toggle {
                border-color: var(--cv-border);
                background: var(--cv-surface);
                color: var(--cv-text-muted);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
            }

            .dark .auth-theme-toggle:hover {
                background: var(--cv-surface-soft);
                color: var(--cv-text);
            }

            @media (min-width: 1024px) {
                .auth-shell {
                    grid-template-columns: 420px 390px;
                    padding-inline: 2rem;
                }

                .auth-panel {
                    display: block;
                }

                .auth-mobile-brand {
                    display: none;
                }
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="auth-page">
            <button
                type="button"
                class="auth-theme-toggle"
                x-data
                x-on:click="$store.theme.toggle()"
                x-bind:aria-label="$store.theme.isDark() ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
            >
                <svg x-show="! $store.theme.isDark()" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.5 6.5 0 0 0 9.8 9.8Z" />
                </svg>
                <svg x-cloak x-show="$store.theme.isDark()" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="4" />
                    <path stroke-linecap="round" d="M12 2.75v2M12 19.25v2M4.42 4.42l1.42 1.42M18.16 18.16l1.42 1.42M2.75 12h2M19.25 12h2M4.42 19.58l1.42-1.42M18.16 5.84l1.42-1.42" />
                </svg>
                <span x-text="$store.theme.isDark() ? 'Claro' : 'Oscuro'"></span>
            </button>

            <div class="auth-shell">
                <section class="auth-panel">
                    <div class="auth-panel-inner">
                        <a href="{{ url('/') }}" class="auth-brand">
                            <span class="auth-mark">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 4.75h7.2L18 8.55v10.7H7V4.75Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M14 5v4h4M9.75 12h5.5M9.75 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <span>CV Studio</span>
                        </a>

                        <div class="auth-panel-copy">
                            <p class="auth-panel-kicker">Acceso para reclutadores</p>
                            <h1 class="auth-panel-title">Talentos, CVs y vacantes bajo control.</h1>
                            <p class="auth-panel-text">
                                Entra al panel para gestionar postulantes, crear curriculums profesionales, organizar companias y dar seguimiento a cada oportunidad laboral.
                            </p>
                        </div>

                        <div class="auth-panel-list">
                            <span><i class="auth-dot green"></i>Pipeline de postulaciones y entrevistas</span>
                            <span><i class="auth-dot gold"></i>Plantillas de CV listas para descargar</span>
                            <span><i class="auth-dot blue"></i>Agenda y perfiles publicos de talento</span>
                        </div>
                    </div>
                </section>

                <main class="auth-main">
                    <div class="auth-card-wrap">
                        <div class="auth-mobile-brand">
                            <a href="{{ url('/') }}">
                                <span class="auth-mark">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M7 4.75h7.2L18 8.55v10.7H7V4.75Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                        <path d="M14 5v4h4M9.75 12h5.5M9.75 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <span>CV Studio</span>
                            </a>
                        </div>

                        <div class="auth-card">
                            {{ $slot }}
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
