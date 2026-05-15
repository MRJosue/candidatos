<x-guest-layout>
    <div class="mb-5 text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--cv-accent)]">Bienvenido de vuelta</p>
        <h2 class="mt-2 text-2xl font-bold tracking-normal text-[var(--cv-text)]">Inicia sesion</h2>
        <p class="mx-auto mt-2 max-w-xs text-sm leading-5 text-[var(--cv-text-muted)]">
            Accede al dashboard para revisar talentos, postulaciones, CVs y proximas citas.
        </p>
    </div>

    <x-auth-session-status class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electronico" class="text-sm font-semibold text-[var(--cv-text)]" />
            <x-text-input id="email" class="mt-1.5 block w-full rounded-lg border-[var(--cv-border)] bg-[var(--cv-surface)] px-3 py-2.5 text-sm text-[var(--cv-text)] shadow-sm focus:border-[var(--cv-accent)] focus:ring-[var(--cv-accent)]" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="reclutador@empresa.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Contrasena" class="text-sm font-semibold text-[var(--cv-text)]" />

            <x-text-input id="password" class="mt-1.5 block w-full rounded-lg border-[var(--cv-border)] bg-[var(--cv-surface)] px-3 py-2.5 text-sm text-[var(--cv-text)] shadow-sm focus:border-[var(--cv-accent)] focus:ring-[var(--cv-accent)]"
                type="password"
                name="password"
                required autocomplete="current-password"
                placeholder="Tu contrasena" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-[var(--cv-border)] text-[var(--cv-accent)] shadow-sm focus:ring-[var(--cv-accent)]" name="remember">
                <span class="ms-2 text-sm text-[var(--cv-text-muted)]">Mantener sesion activa</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-[var(--cv-accent)] hover:text-[var(--cv-accent-hover)] focus:outline-none focus:ring-2 focus:ring-[var(--cv-accent)] focus:ring-offset-2" href="{{ route('password.request') }}">
                    Olvide mi contrasena
                </a>
            @endif
        </div>

        <div class="mt-6">
            <x-primary-button class="flex w-full justify-center rounded-lg bg-[var(--cv-accent)] px-4 py-2.5 text-sm font-bold hover:bg-[var(--cv-accent-hover)] focus:bg-[var(--cv-accent-hover)] active:bg-[var(--cv-text)]">
                Entrar al panel
            </x-primary-button>
        </div>

        @if (Route::has('register'))
            <p class="mt-5 text-center text-sm text-[var(--cv-text-muted)]">
                No tienes cuenta?
                <a href="{{ route('register') }}" class="font-semibold text-[var(--cv-accent)] hover:text-[var(--cv-accent-hover)]">Crea una cuenta</a>
            </p>
        @endif
    </form>
</x-guest-layout>
