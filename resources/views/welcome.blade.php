<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'CV Studio') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <style>
            :root {
                color-scheme: light;
                --ink: #172033;
                --muted: #68748a;
                --line: #dfe5ee;
                --soft: #f5f7fb;
                --brand: #3157d5;
                --brand-dark: #1f3f9f;
                --mint: #16a085;
                --gold: #d59124;
                --paper: #ffffff;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background:
                    linear-gradient(135deg, rgba(49, 87, 213, 0.08), transparent 34%),
                    linear-gradient(315deg, rgba(22, 160, 133, 0.1), transparent 30%),
                    #f8fafc;
                color: var(--ink);
                font-family: Figtree, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            .page {
                min-height: 100vh;
                padding: 28px;
            }

            .shell {
                width: min(1180px, 100%);
                margin: 0 auto;
            }

            .topbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                padding: 10px 0 42px;
            }

            .brand {
                display: flex;
                align-items: center;
                gap: 12px;
                font-weight: 700;
                letter-spacing: 0;
            }

            .brand-mark {
                display: grid;
                width: 42px;
                height: 42px;
                place-items: center;
                border: 1px solid rgba(49, 87, 213, 0.24);
                border-radius: 8px;
                background: var(--paper);
                color: var(--brand);
                box-shadow: 0 14px 32px rgba(23, 32, 51, 0.08);
            }

            .brand-mark svg,
            .icon svg {
                width: 20px;
                height: 20px;
            }

            .nav {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: flex-end;
                gap: 10px;
            }

            .link {
                color: var(--muted);
                font-size: 14px;
                font-weight: 600;
                padding: 10px 12px;
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 42px;
                border-radius: 8px;
                border: 1px solid var(--line);
                padding: 0 16px;
                background: var(--paper);
                color: var(--ink);
                font-size: 14px;
                font-weight: 700;
                box-shadow: 0 1px 0 rgba(23, 32, 51, 0.03);
            }

            .button.primary {
                border-color: var(--brand);
                background: var(--brand);
                color: #fff;
                box-shadow: 0 16px 30px rgba(49, 87, 213, 0.22);
            }

            .hero {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(360px, 520px);
                gap: 48px;
                align-items: center;
                padding-bottom: 42px;
            }

            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin: 0 0 18px;
                color: var(--brand-dark);
                font-size: 13px;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .eyebrow::before {
                content: "";
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: var(--mint);
            }

            h1 {
                max-width: 760px;
                margin: 0;
                font-size: clamp(44px, 7vw, 78px);
                line-height: 0.98;
                letter-spacing: 0;
            }

            .lead {
                max-width: 670px;
                margin: 22px 0 0;
                color: var(--muted);
                font-size: clamp(17px, 2vw, 20px);
                line-height: 1.65;
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 30px;
            }

            .metrics {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
                margin-top: 36px;
                max-width: 680px;
            }

            .metric,
            .preview,
            .module {
                border: 1px solid var(--line);
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.86);
                box-shadow: 0 20px 50px rgba(23, 32, 51, 0.08);
            }

            .metric {
                padding: 16px;
            }

            .metric strong {
                display: block;
                font-size: 25px;
                line-height: 1;
            }

            .metric span {
                display: block;
                margin-top: 8px;
                color: var(--muted);
                font-size: 13px;
                line-height: 1.35;
            }

            .preview {
                overflow: hidden;
            }

            .preview-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                border-bottom: 1px solid var(--line);
                padding: 18px;
                background: var(--paper);
            }

            .preview-title {
                margin: 0;
                font-size: 15px;
                font-weight: 700;
            }

            .status {
                border-radius: 999px;
                background: rgba(22, 160, 133, 0.12);
                color: #08745f;
                padding: 6px 10px;
                font-size: 12px;
                font-weight: 700;
            }

            .pipeline {
                display: grid;
                gap: 10px;
                padding: 18px;
            }

            .candidate {
                display: grid;
                grid-template-columns: 42px 1fr auto;
                gap: 12px;
                align-items: center;
                border: 1px solid #e8edf4;
                border-radius: 8px;
                padding: 12px;
                background: #fff;
            }

            .avatar {
                display: grid;
                width: 42px;
                height: 42px;
                place-items: center;
                border-radius: 8px;
                background: #eef3ff;
                color: var(--brand);
                font-weight: 800;
            }

            .candidate strong {
                display: block;
                font-size: 14px;
            }

            .candidate span {
                color: var(--muted);
                font-size: 12px;
            }

            .tag {
                border-radius: 999px;
                background: var(--soft);
                color: #516078;
                padding: 6px 10px;
                font-size: 12px;
                font-weight: 700;
                white-space: nowrap;
            }

            .cv-sheet {
                margin: 0 18px 18px;
                border: 1px solid #e5ebf3;
                border-radius: 8px;
                background: linear-gradient(90deg, #24406f 0 31%, #ffffff 31%);
                min-height: 250px;
                padding: 22px;
                box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.4);
            }

            .cv-content {
                margin-left: 34%;
            }

            .line {
                height: 10px;
                border-radius: 999px;
                background: #d9e1ed;
                margin-bottom: 10px;
            }

            .line.short {
                width: 46%;
                background: var(--gold);
            }

            .line.medium {
                width: 72%;
            }

            .line.long {
                width: 92%;
            }

            .modules {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 14px;
                padding-bottom: 34px;
            }

            .module {
                padding: 18px;
            }

            .icon {
                display: grid;
                width: 38px;
                height: 38px;
                place-items: center;
                border-radius: 8px;
                background: #eef3ff;
                color: var(--brand);
            }

            .module h2 {
                margin: 14px 0 8px;
                font-size: 16px;
            }

            .module p {
                margin: 0;
                color: var(--muted);
                font-size: 14px;
                line-height: 1.55;
            }

            @media (max-width: 900px) {
                .page {
                    padding: 18px;
                }

                .topbar,
                .hero {
                    gap: 28px;
                }

                .hero,
                .modules {
                    grid-template-columns: 1fr;
                }

                .preview {
                    max-width: 620px;
                }
            }

            @media (max-width: 620px) {
                .topbar {
                    align-items: flex-start;
                    flex-direction: column;
                    padding-bottom: 28px;
                }

                .nav {
                    justify-content: flex-start;
                    width: 100%;
                }

                .metrics {
                    grid-template-columns: 1fr;
                }

                .candidate {
                    grid-template-columns: 42px 1fr;
                }

                .tag {
                    grid-column: 2;
                    justify-self: start;
                }

                .cv-sheet {
                    min-height: 210px;
                    padding: 18px;
                }
            }
        </style>
    </head>
    <body>
        <div class="page">
            <div class="shell">
                <header class="topbar" aria-label="Principal">
                    <a class="brand" href="{{ url('/') }}">
                        <span class="brand-mark" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M7 4.75h7.2L18 8.55v10.7H7V4.75Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                <path d="M14 5v4h4M9.75 12h5.5M9.75 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>CV Studio</span>
                    </a>

                    @if (Route::has('login'))
                        <nav class="nav" aria-label="Acceso">
                            @auth
                                <a class="button primary" href="{{ url('/dashboard') }}">Ir al dashboard</a>
                            @else
                                <a class="link" href="{{ route('login') }}">Iniciar sesion</a>

                                @if (Route::has('register'))
                                    <a class="button" href="{{ route('register') }}">Crear cuenta</a>
                                @endif
                            @endauth
                        </nav>
                    @endif
                </header>

                <main>
                    <section class="hero">
                        <div>
                            <p class="eyebrow">Gestion de reclutamiento y CVs</p>
                            <h1>CV Studio ordena talento, vacantes y curriculums en un solo lugar.</h1>
                            <p class="lead">
                                Administra perfiles de postulantes, genera CVs profesionales, da seguimiento a postulaciones,
                                controla companias y agenda citas sin perder el hilo de cada proceso.
                            </p>

                            <div class="actions">
                                @auth
                                    <a class="button primary" href="{{ route('dashboard') }}">Abrir panel</a>
                                    <a class="button" href="{{ route('talents.index') }}">Ver talentos</a>
                                @else
                                    <a class="button primary" href="{{ route('login') }}">Entrar al sistema</a>
                                    @if (Route::has('register'))
                                        <a class="button" href="{{ route('register') }}">Registrar usuario</a>
                                    @endif
                                @endauth
                            </div>

                            <div class="metrics" aria-label="Modulos principales">
                                <div class="metric">
                                    <strong>6</strong>
                                    <span>modulos conectados para el reclutador</span>
                                </div>
                                <div class="metric">
                                    <strong>CV</strong>
                                    <span>perfiles editables, plantillas y descarga</span>
                                </div>
                                <div class="metric">
                                    <strong>Agenda</strong>
                                    <span>citas y seguimiento del pipeline</span>
                                </div>
                            </div>
                        </div>

                        <aside class="preview" aria-label="Vista previa del panel">
                            <div class="preview-head">
                                <p class="preview-title">Pipeline de postulantes</p>
                                <span class="status">Activo</span>
                            </div>

                            <div class="pipeline">
                                <div class="candidate">
                                    <div class="avatar">AM</div>
                                    <div>
                                        <strong>Ana Martinez</strong>
                                        <span>Frontend Developer · CV actualizado</span>
                                    </div>
                                    <span class="tag">Entrevista</span>
                                </div>
                                <div class="candidate">
                                    <div class="avatar">JR</div>
                                    <div>
                                        <strong>Jose Ramirez</strong>
                                        <span>Project Manager · vacante asignada</span>
                                    </div>
                                    <span class="tag">Revision</span>
                                </div>
                                <div class="candidate">
                                    <div class="avatar">LC</div>
                                    <div>
                                        <strong>Laura Cruz</strong>
                                        <span>UX Researcher · perfil publico listo</span>
                                    </div>
                                    <span class="tag">Finalista</span>
                                </div>
                            </div>

                            <div class="cv-sheet" aria-hidden="true">
                                <div class="cv-content">
                                    <div class="line short"></div>
                                    <div class="line long"></div>
                                    <div class="line medium"></div>
                                    <br>
                                    <div class="line long"></div>
                                    <div class="line long"></div>
                                    <div class="line medium"></div>
                                    <br>
                                    <div class="line short"></div>
                                    <div class="line medium"></div>
                                </div>
                            </div>
                        </aside>
                    </section>

                    <section class="modules" aria-label="Funciones de CV Studio">
                        <article class="module">
                            <span class="icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M16 19v-1.5A3.5 3.5 0 0 0 12.5 14h-5A3.5 3.5 0 0 0 4 17.5V19M10 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM20 19v-1.2a3 3 0 0 0-2.4-2.94M16 5.18a3 3 0 0 1 0 5.64" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <h2>Talentos</h2>
                            <p>Registra candidatos, estado, datos clave y perfiles publicos para actualizar informacion.</p>
                        </article>

                        <article class="module">
                            <span class="icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M6 4.75h8l4 4v10.5H6V4.75Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <path d="M14 5v4h4M9 13h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <h2>CVs y plantillas</h2>
                            <p>Crea curriculums por talento, cambia secciones, previsualiza y descarga documentos.</p>
                        </article>

                        <article class="module">
                            <span class="icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4.75 8.5h14.5M7.5 4.75v3.5M16.5 4.75v3.5M6.25 6.25h11.5c.83 0 1.5.67 1.5 1.5v10c0 .83-.67 1.5-1.5 1.5H6.25c-.83 0-1.5-.67-1.5-1.5v-10c0-.83.67-1.5 1.5-1.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <h2>Agenda</h2>
                            <p>Programa citas y manten el calendario de servicios conectado al proceso del candidato.</p>
                        </article>

                        <article class="module">
                            <span class="icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M5 19.25V6.75C5 5.78 5.78 5 6.75 5h10.5c.97 0 1.75.78 1.75 1.75v12.5M3.75 19.25h16.5M8.5 9h2M13.5 9h2M8.5 12.5h2M13.5 12.5h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <h2>Vacantes</h2>
                            <p>Relaciona companias, vacantes y postulaciones para seguir cada oportunidad laboral.</p>
                        </article>
                    </section>
                </main>
            </div>
        </div>
    </body>
</html>
