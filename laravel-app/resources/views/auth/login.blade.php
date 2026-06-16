<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-slate-100">
            Einloggen
        </h1>
        <p class="mt-2 text-sm text-slate-400">
            Melde dich an, um dein Stechen-MMO-Konto zu betreten.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4 text-amber-300" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-200">
                E-Mail
            </label>

            <input
                id="email"
                class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-slate-100 shadow-sm outline-none transition placeholder:text-slate-600 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
            />

            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-200">
                Passwort
            </label>

            <input
                id="password"
                class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-slate-100 shadow-sm outline-none transition placeholder:text-slate-600 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-slate-600 bg-slate-950 text-amber-500 shadow-sm focus:ring-amber-500"
                    name="remember"
                >
                <span class="ms-2 text-sm text-slate-400">
                    Eingeloggt bleiben
                </span>
            </label>

            @if (Route::has('password.request'))
                <a
                    class="text-sm text-amber-300 underline-offset-4 transition hover:text-amber-200 hover:underline"
                    href="{{ route('password.request') }}"
                >
                    Passwort vergessen?
                </a>
            @endif
        </div>

        <div>
            <button
                type="submit"
                class="w-full rounded-lg bg-amber-400 px-4 py-2.5 text-sm font-bold uppercase tracking-wide text-slate-950 shadow-lg shadow-amber-950/30 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-slate-900"
            >
                Einloggen
            </button>
        </div>

        <p class="text-center text-sm text-slate-400">
            Noch kein Konto?
            <a href="{{ route('register') }}" class="font-medium text-amber-300 underline-offset-4 transition hover:text-amber-200 hover:underline">
                Jetzt registrieren
            </a>
        </p>
    </form>
</x-guest-layout>
