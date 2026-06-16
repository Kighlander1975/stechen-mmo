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

        <!-- Legal Consent -->
        <div class="rounded-lg border border-slate-700 bg-slate-950/60 px-4 py-4">
            <label for="legal_accepted" class="flex items-start gap-3">
                <input
                    id="legal_accepted"
                    name="legal_accepted"
                    type="checkbox"
                    class="mt-1 rounded border-slate-600 bg-slate-950 text-amber-500 shadow-sm focus:ring-amber-500"
                    required
                    {{ old('legal_accepted') ? 'checked' : '' }}
                >

                <span class="text-sm leading-6 text-slate-300">
                    Ich akzeptiere die

                    <button
                        type="button"
                        class="font-medium text-amber-300 underline-offset-4 transition hover:text-amber-200 hover:underline"
                        onclick="document.getElementById('terms-modal').showModal()"
                    >
                        AGB
                    </button>,

                    die

                    <button
                        type="button"
                        class="font-medium text-amber-300 underline-offset-4 transition hover:text-amber-200 hover:underline"
                        onclick="document.getElementById('privacy-modal').showModal()"
                    >
                        Datenschutzbestimmungen
                    </button>

                    und die

                    <button
                        type="button"
                        class="font-medium text-amber-300 underline-offset-4 transition hover:text-amber-200 hover:underline"
                        onclick="document.getElementById('rules-modal').showModal()"
                    >
                        Spielregeln
                    </button>.
                </span>
            </label>

            <x-input-error :messages="$errors->get('legal_accepted')" class="mt-2" />
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

    <!-- Terms Modal -->
    <dialog id="terms-modal" class="w-full max-w-2xl rounded-2xl border border-amber-500/20 bg-slate-900 p-0 text-slate-100 shadow-2xl backdrop:bg-black/70">
        <div class="border-b border-slate-800 px-6 py-4">
            <h2 class="text-xl font-bold text-amber-300">
                Allgemeine Geschäftsbedingungen
            </h2>
        </div>

        <div class="max-h-96 space-y-4 overflow-y-auto px-6 py-5 text-sm leading-6 text-slate-300">
            <p>
                Diese AGB befinden sich aktuell im Aufbau.
            </p>

            <p>
                Stechen MMO befindet sich derzeit in einer frühen Entwicklungsphase.
                Echtgeld-, Wallet-, Zahlungs- und Auszahlungsfunktionen sind aktuell nicht aktiv.
            </p>

            <p>
                Vor einer produktiven Veröffentlichung werden die AGB vollständig ausgearbeitet,
                geprüft und versioniert.
            </p>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-800 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ url('/terms') }}" target="_blank" class="text-sm font-medium text-amber-300 underline-offset-4 hover:text-amber-200 hover:underline">
                Als eigene Seite öffnen
            </a>

            <button
                type="button"
                class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-bold uppercase tracking-wide text-slate-950 transition hover:bg-amber-300"
                onclick="document.getElementById('terms-modal').close()"
            >
                Schließen
            </button>
        </div>
    </dialog>

    <!-- Privacy Modal -->
    <dialog id="privacy-modal" class="w-full max-w-2xl rounded-2xl border border-amber-500/20 bg-slate-900 p-0 text-slate-100 shadow-2xl backdrop:bg-black/70">
        <div class="border-b border-slate-800 px-6 py-4">
            <h2 class="text-xl font-bold text-amber-300">
                Datenschutzbestimmungen
            </h2>
        </div>

        <div class="max-h-96 space-y-4 overflow-y-auto px-6 py-5 text-sm leading-6 text-slate-300">
            <p>
                Diese Datenschutzbestimmungen befinden sich aktuell im Aufbau.
            </p>

            <p>
                Für die lokale Entwicklungsphase werden nur die technisch notwendigen Daten verarbeitet,
                die für Registrierung, Login und Sitzungsverwaltung erforderlich sind.
            </p>

            <p>
                Echtgeld-, Wallet-, Zahlungs- und Auszahlungsfunktionen sind aktuell nicht aktiv.
            </p>

            <p>
                Vor einer produktiven Veröffentlichung werden die Datenschutzbestimmungen vollständig
                ausgearbeitet, geprüft und versioniert.
            </p>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-800 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ url('/privacy') }}" target="_blank" class="text-sm font-medium text-amber-300 underline-offset-4 hover:text-amber-200 hover:underline">
                Als eigene Seite öffnen
            </a>

            <button
                type="button"
                class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-bold uppercase tracking-wide text-slate-950 transition hover:bg-amber-300"
                onclick="document.getElementById('privacy-modal').close()"
            >
                Schließen
            </button>
        </div>
    </dialog>

    <!-- Rules Modal -->
    <dialog id="rules-modal" class="w-full max-w-2xl rounded-2xl border border-amber-500/20 bg-slate-900 p-0 text-slate-100 shadow-2xl backdrop:bg-black/70">
        <div class="border-b border-slate-800 px-6 py-4">
            <h2 class="text-xl font-bold text-amber-300">
                Spielregeln
            </h2>
        </div>

        <div class="max-h-96 space-y-4 overflow-y-auto px-6 py-5 text-sm leading-6 text-slate-300">
            <p>
                Die Spielregeln beschreiben die Grundlagen von Stechen MMO, den fairen Umgang miteinander
                und die Rahmenbedingungen für spätere Spielrunden.
            </p>

            <p>
                Diese Regeln werden während der Entwicklung weiter ausgearbeitet und versioniert.
            </p>

            <p>
                Für die aktuelle Entwicklungsphase gilt: Echtgeld-, Wallet-, Zahlungs- und Auszahlungsfunktionen
                sind nicht aktiv.
            </p>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-800 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ url('/rules') }}" target="_blank" class="text-sm font-medium text-amber-300 underline-offset-4 hover:text-amber-200 hover:underline">
                Als eigene Seite öffnen
            </a>

            <button
                type="button"
                class="rounded-lg bg-amber-400 px-4 py-2 text-sm font-bold uppercase tracking-wide text-slate-950 transition hover:bg-amber-300"
                onclick="document.getElementById('rules-modal').close()"
            >
                Schließen
            </button>
        </div>
    </dialog>
</x-guest-layout>
