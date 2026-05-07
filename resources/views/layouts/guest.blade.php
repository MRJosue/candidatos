<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CV Studio') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @wireUiScripts
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .auth-page {
                min-height: 100vh;
                background: #f8fafc;
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
                background: #172033;
                color: #ffffff;
                padding: 2rem;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
            }

            .auth-panel::before {
                content: "";
                position: absolute;
                inset: 0 0 auto;
                height: 4px;
                background: linear-gradient(90deg, #3157d5, #16a085, #d59124);
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
                color: #ffffff;
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
                color: #7de0cd;
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
                color: #cbd5e1;
                font-size: 0.875rem;
                line-height: 1.65;
            }

            .auth-panel-list {
                display: grid;
                gap: 0.75rem;
                color: #cbd5e1;
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
                background: #16a085;
            }

            .auth-dot.gold {
                background: #d59124;
            }

            .auth-dot.blue {
                background: #6f8df4;
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
                color: #172033;
                font-weight: 700;
            }

            .auth-mobile-brand .auth-mark {
                border-color: #e2e8f0;
                background: #ffffff;
                color: #3157d5;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            }

            .auth-card {
                border: 1px solid #dfe5ee;
                border-radius: 8px;
                background: #ffffff;
                padding: 1.5rem;
                box-shadow: 0 20px 45px rgba(15, 23, 42, 0.14);
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
