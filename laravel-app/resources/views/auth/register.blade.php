<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-slate-100">
            Konto erstellen
        </h1>
        <p class="mt-2 text-sm text-slate-400">
            Registriere dich für Stechen MMO.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-slate-200">
                Anzeigename
            </label>

            <input
                id="name"
                class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-slate-100 shadow-sm outline-none transition placeholder:text-slate-600 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30"
                type="text"
                name="name"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
            />

            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

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
                autocomplete="new-password"
            />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-200">
                Passwort bestätigen
            </label>

            <input
                id="password_confirmation"
                class="mt-2 block w-full rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-2 text-slate-100 shadow-sm outline-none transition placeholder:text-slate-600 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
            />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="rounded-lg border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-xs leading-5 text-amber-100">
            Mit der Registrierung wird zunächst nur ein Spielkonto erstellt.
            Echtgeld-, Wallet- und Zahlungsfunktionen sind noch nicht aktiv.
        </div>

        <div>
            <button
                type="submit"
                class="w-full rounded-lg bg-amber-400 px-4 py-2.5 text-sm font-bold uppercase tracking-wide text-slate-950 shadow-lg shadow-amber-950/30 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-slate-900"
            >
                Registrieren
            </button>
        </div>

        <p class="text-center text-sm text-slate-400">
            Bereits registriert?
            <a href="{{ route('login') }}" class="font-medium text-amber-300 underline-offset-4 transition hover:text-amber-200 hover:underline">
                Zum Login
            </a>
        </p>
    </form>
</x-guest-layout>
