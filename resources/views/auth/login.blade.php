<x-guest-layout>
    <div class="mb-5 text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#3157d5]">Bienvenido de vuelta</p>
        <h2 class="mt-2 text-2xl font-bold tracking-normal text-slate-950">Inicia sesion</h2>
        <p class="mx-auto mt-2 max-w-xs text-sm leading-5 text-slate-500">
            Accede al dashboard para revisar talentos, postulaciones, CVs y proximas citas.
        </p>
    </div>

    <x-auth-session-status class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electronico" class="text-sm font-semibold text-slate-700" />
            <x-text-input id="email" class="mt-1.5 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-[#3157d5] focus:ring-[#3157d5]" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="reclutador@empresa.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Contrasena" class="text-sm font-semibold text-slate-700" />

            <x-text-input id="password" class="mt-1.5 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-[#3157d5] focus:ring-[#3157d5]"
                type="password"
                name="password"
                required autocomplete="current-password"
                placeholder="Tu contrasena" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-[#3157d5] shadow-sm focus:ring-[#3157d5]" name="remember">
                <span class="ms-2 text-sm text-slate-600">Mantener sesion activa</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-[#3157d5] hover:text-[#1f3f9f] focus:outline-none focus:ring-2 focus:ring-[#3157d5] focus:ring-offset-2" href="{{ route('password.request') }}">
                    Olvide mi contrasena
                </a>
            @endif
        </div>

        <div class="mt-6">
            <x-primary-button class="flex w-full justify-center rounded-lg bg-[#3157d5] px-4 py-2.5 text-sm font-bold hover:bg-[#1f3f9f] focus:bg-[#1f3f9f] active:bg-[#172033]">
                Entrar al panel
            </x-primary-button>
        </div>

        @if (Route::has('register'))
            <p class="mt-5 text-center text-sm text-slate-500">
                No tienes cuenta?
                <a href="{{ route('register') }}" class="font-semibold text-[#3157d5] hover:text-[#1f3f9f]">Crea una cuenta</a>
            </p>
        @endif
    </form>
</x-guest-layout>
