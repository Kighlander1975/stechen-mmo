<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-slate-100">
            Passwort vergessen?
        </h1>
        <p class="mt-2 text-sm leading-6 text-slate-400">
            Kein Problem. Gib deine E-Mail-Adresse ein und wir senden dir einen Link,
            mit dem du ein neues Passwort setzen kannst.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4 text-amber-300" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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

        <div class="rounded-lg border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-xs leading-5 text-amber-100">
            Falls deine E-Mail-Adresse bekannt ist, erhältst du einen Link zum Zurücksetzen deines Passworts.
            Prüfe danach bitte auch deinen Spam-Ordner.
        </div>

        <div>
            <button
                type="submit"
                class="w-full rounded-lg bg-amber-400 px-4 py-2.5 text-sm font-bold uppercase tracking-wide text-slate-950 shadow-lg shadow-amber-950/30 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-slate-900"
            >
                Passwort-Link senden
            </button>
        </div>

        <p class="text-center text-sm text-slate-400">
            Passwort wieder eingefallen?
            <a href="{{ route('login') }}" class="font-medium text-amber-300 underline-offset-4 transition hover:text-amber-200 hover:underline">
                Zurück zum Login
            </a>
        </p>
    </form>
</x-guest-layout>
