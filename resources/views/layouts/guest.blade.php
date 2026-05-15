<!DOCTYPE html>
@php
    $applicationTheme = Auth::user()?->applicationTheme ?? \App\Models\ApplicationTheme::default();
    $appearanceMode = Auth::user()?->theme_mode ?? 'system';
    $backgroundImageUrl = $applicationTheme->backgroundImageUrl();
    $backgroundImageCss = $backgroundImageUrl
        ? 'url('.json_encode($backgroundImageUrl, JSON_UNESCAPED_SLASHES).')'
        : 'none';
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
                --cv-bg-image: {!! $backgroundImageCss !!};
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
                    linear-gradient(135deg, color-mix(in srgb, var(--cv-accent) 8%, transparent), transparent 34%),
                    linear-gradient(315deg, rgba(22, 160, 133, 0.1), transparent 30%),
                    var(--cv-bg-image),
                    var(--cv-bg);
                background-position: center;
                background-size: cover;
                padding: 28px;
                transition: background-color 150ms ease, color 150ms ease;
            }

            .auth-shell {
                width: min(1180px, 100%);
                min-height: calc(100vh - 56px);
                margin: 0 auto;
            }

            .auth-topbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                padding: 10px 0 42px;
            }

            .auth-nav {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: flex-end;
                gap: 10px;
            }

            .auth-nav-link,
            .auth-theme-toggle {
                display: inline-flex;
                min-height: 42px;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                border: 0;
                background: transparent;
                color: var(--cv-text-muted);
                padding: 0 12px;
                font-size: 14px;
                font-weight: 600;
            }

            .auth-nav-link:hover,
            .auth-theme-toggle:hover {
                color: var(--cv-text);
            }

            .auth-nav-button {
                display: inline-flex;
                min-height: 42px;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--cv-border);
                border-radius: 8px;
                background: var(--cv-surface);
                color: var(--cv-text);
                padding: 0 16px;
                font-size: 14px;
                font-weight: 700;
                box-shadow: 0 1px 0 color-mix(in srgb, var(--cv-text) 3%, transparent);
            }

            .auth-nav-button:hover {
                border-color: color-mix(in srgb, var(--cv-accent) 32%, var(--cv-border));
                color: var(--cv-accent-hover);
            }

            .auth-content {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(360px, 430px);
                gap: 48px;
                align-items: center;
            }

            .auth-brand {
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 700;
            }

            .auth-mark {
                display: grid;
                width: 42px;
                height: 42px;
                place-items: center;
                border: 1px solid color-mix(in srgb, var(--cv-accent) 24%, transparent);
                border-radius: 8px;
                background: var(--cv-surface);
                color: var(--cv-accent);
                box-shadow: 0 14px 32px color-mix(in srgb, var(--cv-text) 8%, transparent);
            }

            .auth-mark svg {
                width: 20px;
                height: 20px;
            }

            .auth-panel {
                color: var(--cv-text);
            }

            .auth-panel-copy {
                max-width: 650px;
            }

            .auth-panel-kicker {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin: 0 0 18px;
                color: var(--cv-accent-hover);
                font-size: 13px;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .auth-panel-kicker::before {
                content: "";
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: #16a085;
            }

            .auth-panel-title {
                margin: 0;
                max-width: 720px;
                font-size: clamp(44px, 6vw, 72px);
                font-weight: 700;
                line-height: 0.98;
                letter-spacing: 0;
            }

            .auth-panel-text {
                max-width: 640px;
                margin: 22px 0 0;
                color: var(--cv-text-muted);
                font-size: clamp(17px, 2vw, 20px);
                line-height: 1.65;
            }

            .auth-metrics {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
                max-width: 680px;
                margin-top: 36px;
            }

            .auth-metric,
            .auth-preview {
                border: 1px solid var(--cv-border);
                border-radius: 8px;
                background: color-mix(in srgb, var(--cv-surface) 86%, transparent);
                box-shadow: 0 20px 50px color-mix(in srgb, var(--cv-text) 8%, transparent);
            }

            .auth-metric {
                padding: 16px;
            }

            .auth-metric strong {
                display: block;
                font-size: 25px;
                line-height: 1;
            }

            .auth-metric span {
                display: block;
                margin-top: 8px;
                color: var(--cv-text-muted);
                font-size: 13px;
                line-height: 1.35;
            }

            .auth-preview {
                max-width: 560px;
                margin-top: 14px;
                overflow: hidden;
            }

            .auth-preview-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                border-bottom: 1px solid var(--cv-border);
                padding: 18px;
                background: var(--cv-surface);
            }

            .auth-preview-title {
                margin: 0;
                font-size: 15px;
                font-weight: 700;
            }

            .auth-status {
                border-radius: 999px;
                background: rgba(22, 160, 133, 0.12);
                color: #08745f;
                padding: 6px 10px;
                font-size: 12px;
                font-weight: 700;
            }

            .auth-preview-body {
                display: grid;
                gap: 10px;
                padding: 18px;
            }

            .auth-candidate {
                display: grid;
                grid-template-columns: 42px 1fr auto;
                gap: 12px;
                align-items: center;
                border: 1px solid color-mix(in srgb, var(--cv-border) 72%, var(--cv-surface));
                border-radius: 8px;
                padding: 12px;
                background: var(--cv-surface);
            }

            .auth-avatar {
                display: grid;
                width: 42px;
                height: 42px;
                place-items: center;
                border-radius: 8px;
                background: color-mix(in srgb, var(--cv-accent) 10%, var(--cv-surface));
                color: var(--cv-accent);
                font-weight: 800;
            }

            .auth-candidate strong {
                display: block;
                font-size: 14px;
            }

            .auth-candidate-meta {
                color: var(--cv-text-muted);
                font-size: 12px;
            }

            .auth-tag {
                border-radius: 999px;
                background: var(--cv-surface-soft);
                color: color-mix(in srgb, var(--cv-text-muted) 82%, var(--cv-text));
                padding: 6px 10px;
                font-size: 12px;
                font-weight: 700;
                white-space: nowrap;
            }

            .auth-main {
                display: flex;
                width: min(430px, 100%);
                align-items: center;
                justify-content: center;
            }

            .auth-card-wrap {
                width: 100%;
            }

            .auth-mobile-brand {
                display: none;
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
                box-shadow: 0 1px 2px color-mix(in srgb, var(--cv-text) 10%, transparent);
            }

            .auth-card {
                border: 1px solid var(--cv-border);
                border-radius: 8px;
                background: color-mix(in srgb, var(--cv-surface) 92%, transparent);
                padding: 1.75rem;
                box-shadow: 0 20px 50px color-mix(in srgb, var(--cv-text) 8%, transparent);
            }

            .dark .auth-page {
                background:
                    linear-gradient(135deg, color-mix(in srgb, var(--cv-accent) 12%, transparent), transparent 34%),
                    linear-gradient(315deg, rgba(22, 160, 133, 0.1), transparent 30%),
                    var(--cv-bg-image),
                    var(--cv-bg);
                background-position: center;
                background-size: cover;
            }

            .dark .auth-mobile-brand a {
                color: var(--cv-text);
            }

            .dark .auth-mobile-brand .auth-mark,
            .dark .auth-card,
            .dark .auth-metric,
            .dark .auth-preview {
                border-color: var(--cv-border);
                background: var(--cv-surface);
                color: var(--cv-text);
            }

            .dark .auth-card {
                box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
            }

            @media (max-width: 900px) {
                .auth-page {
                    padding: 18px;
                }

                .auth-topbar,
                .auth-content {
                    gap: 28px;
                }

                .auth-content {
                    grid-template-columns: 1fr;
                }

                .auth-main {
                    width: min(620px, 100%);
                    justify-content: flex-start;
                }
            }

            @media (max-width: 620px) {
                .auth-topbar {
                    align-items: flex-start;
                    flex-direction: column;
                    padding-bottom: 28px;
                }

                .auth-nav {
                    justify-content: flex-start;
                    width: 100%;
                }

                .auth-metrics {
                    grid-template-columns: 1fr;
                }

                .auth-candidate {
                    grid-template-columns: 42px 1fr;
                }

                .auth-tag {
                    grid-column: 2;
                    justify-self: start;
                }
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="auth-page">
            <div class="auth-shell">
                <header class="auth-topbar" aria-label="Principal">
                    <a href="{{ url('/') }}" class="auth-brand">
                        <span class="auth-mark">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 4.75h7.2L18 8.55v10.7H7V4.75Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M14 5v4h4M9.75 12h5.5M9.75 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>CV Studio</span>
                    </a>

                    <nav class="auth-nav" aria-label="Acceso">
                        <a class="auth-nav-link" href="{{ url('/') }}">Inicio</a>
                        @if (Route::has('login') && ! Route::is('login'))
                            <a class="auth-nav-link" href="{{ route('login') }}">Iniciar sesion</a>
                        @endif
                        @if (Route::has('register') && ! Route::is('register'))
                            <a class="auth-nav-button" href="{{ route('register') }}">Crear cuenta</a>
                        @endif
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
                    </nav>
                </header>

                <div class="auth-content">
                    <section class="auth-panel">
                        <div class="auth-panel-copy">
                            <p class="auth-panel-kicker">Gestion de reclutamiento y CVs</p>
                            <h1 class="auth-panel-title">CV Studio ordena talento, vacantes y curriculums.</h1>
                            <p class="auth-panel-text">
                                Accede para administrar perfiles de postulantes, generar CVs profesionales, dar seguimiento a postulaciones y mantener la agenda al dia sin perder el hilo.
                            </p>
                        </div>

                        <div class="auth-metrics" aria-label="Resumen de plataforma">
                            <div class="auth-metric">
                                <strong>6</strong>
                                <span>modulos conectados para el reclutador</span>
                            </div>
                            <div class="auth-metric">
                                <strong>CV</strong>
                                <span>perfiles editables, plantillas y descarga</span>
                            </div>
                            <div class="auth-metric">
                                <strong>Agenda</strong>
                                <span>citas y seguimiento del pipeline</span>
                            </div>
                        </div>

                        <div class="auth-preview" aria-hidden="true">
                            <div class="auth-preview-head">
                                <p class="auth-preview-title">Pipeline de postulantes</p>
                                <span class="auth-status">Activo</span>
                            </div>
                            <div class="auth-preview-body">
                                <div class="auth-candidate">
                                    <span class="auth-avatar">AM</span>
                                    <span><strong>Ana Martinez</strong><span class="auth-candidate-meta">Frontend Developer · CV actualizado</span></span>
                                    <span class="auth-tag">Entrevista</span>
                                </div>
                                <div class="auth-candidate">
                                    <span class="auth-avatar">JR</span>
                                    <span><strong>Jose Ramirez</strong><span class="auth-candidate-meta">Project Manager · vacante asignada</span></span>
                                    <span class="auth-tag">Revision</span>
                                </div>
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
        </div>
    </body>
</html>
