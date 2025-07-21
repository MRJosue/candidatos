<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Gestor de Postulaciones</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /*! tailwindcss v4 ... */
                /* (Aquí va el mismo bloque de estilos inyectado por Laravel/Tailwind. Lo puedes dejar igual) */
            </style>
        @endif
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">

        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a
                            href="{{ url('/admin') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal"
                        >
                            Panel de Postulaciones
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                        >
                            Iniciar sesión
                        </a>
                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                                Crear cuenta
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>

        <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
            <main class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row">
                <div class="text-[13px] leading-[20px] flex-1 p-6 pb-12 lg:p-20 bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-bl-lg rounded-br-lg lg:rounded-tl-lg lg:rounded-br-none">
                    <h1 class="mb-1 font-medium text-2xl text-blue-900 dark:text-white">Gestiona y organiza tus postulaciones</h1>
                    <p class="mb-4 text-[#706f6c] dark:text-[#A1A09A] text-base">
                        Administra fácilmente tus candidatos y tipos de puesto desde un solo lugar.<br>
                        Lleva el control de cada postulación y agiliza tu proceso de selección.
                    </p>
                    <ul class="flex flex-col mb-6">
                        <li class="flex items-center gap-3 py-1">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.386 7.386a1 1 0 01-1.414 0l-3.293-3.293a1 1 0 111.414-1.414l2.586 2.586 6.679-6.679a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span>Registra tus candidatos fácilmente</span>
                        </li>
                        <li class="flex items-center gap-3 py-1">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.386 7.386a1 1 0 01-1.414 0l-3.293-3.293a1 1 0 111.414-1.414l2.586 2.586 6.679-6.679a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span>Gestiona los tipos de puesto a tu medida</span>
                        </li>
                        <li class="flex items-center gap-3 py-1">
                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.386 7.386a1 1 0 01-1.414 0l-3.293-3.293a1 1 0 111.414-1.414l2.586 2.586 6.679-6.679a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span>Centraliza y revisa tus postulaciones</span>
                        </li>
                    </ul>
                    <div class="flex gap-3">
                        <a href="{{ route('login') }}" class="px-5 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 transition">Ir al panel</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-5 py-2 bg-white text-blue-700 border border-blue-600 rounded-md font-medium hover:bg-blue-50 transition">Crear cuenta</a>
                        @endif
                    </div>
                </div>
                <div class="bg-[#fff2f2] dark:bg-[#1D0002] relative lg:-ml-px -mb-px lg:mb-0 rounded-t-lg lg:rounded-t-none lg:rounded-r-lg aspect-[335/376] lg:aspect-auto w-full lg:w-[438px] shrink-0 overflow-hidden flex items-center justify-center">
                    {{-- Logo o Ilustración --}}
                    <svg class="w-48 mx-auto opacity-80" fill="none" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="48" stroke="#f53003" stroke-width="4" fill="#fff"/>
                        <path d="M60 35a10 10 0 1 1-20 0 10 10 0 0 1 20 0z" fill="#f53003" opacity=".15"/>
                        <rect x="35" y="55" width="30" height="20" rx="5" fill="#f53003" opacity=".13"/>
                        <rect x="40" y="60" width="20" height="10" rx="2" fill="#f53003" opacity=".09"/>
                    </svg>
                </div>
            </main>
        </div>
        <footer class="text-center mt-8 text-gray-400 text-xs">
            &copy; {{ date('Y') }} — Gestor de Postulaciones
        </footer>
    </body>
</html>
