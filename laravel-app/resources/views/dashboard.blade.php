<x-app-layout
    header-eyebrow="Spielerkonto"
    :header-title="'Willkommen, '.Auth::user()->name"
    header-status-label="Konto aktiv"
    header-status-tone="success"
>

    <div class="space-y-8">
        <!-- Status Hinweis -->
        <section class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-6 shadow-xl shadow-black/20">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-amber-300">
                        Entwicklungsstatus
                    </h2>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-amber-100">
                        Stechen MMO befindet sich aktuell in einer frühen Entwicklungsphase.
                        Registrierung, Login und Spielerkonto sind aktiv. Echtgeld-, Wallet-,
                        Zahlungs- und Auszahlungsfunktionen sind derzeit deaktiviert.
                    </p>
                </div>

                <span class="inline-flex w-fit rounded-full border border-amber-400/30 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-300">
                    Pre-Alpha
                </span>
            </div>
        </section>

        <!-- Übersicht -->
        <section class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
                <p class="text-sm font-medium text-slate-400">
                    Account
                </p>

                <h3 class="mt-2 text-2xl font-bold text-slate-100">
                    Aktiv
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-400">
                    Dein Spielerkonto wurde erstellt und kann für zukünftige Spielbereiche verwendet werden.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
                <p class="text-sm font-medium text-slate-400">
                    Spielbetrieb
                </p>

                <h3 class="mt-2 text-2xl font-bold text-slate-100">
                    In Vorbereitung
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-400">
                    Spielräume, Rundenverwaltung, Ranglisten und Turniere werden schrittweise ergänzt.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
                <p class="text-sm font-medium text-slate-400">
                    Wallet
                </p>

                <h3 class="mt-2 text-2xl font-bold text-slate-100">
                    Deaktiviert
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-400">
                    Es sind aktuell keine Einzahlungen, Auszahlungen oder Echtgeld-Funktionen aktiv.
                </p>
            </div>
        </section>

        <!-- Hauptbereich -->
        <section class="grid gap-6 lg:grid-cols-3">
            <!-- Spielbereiche -->
            <div class="lg:col-span-2 rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-wide text-amber-400">
                            Spielbereiche
                        </p>

                        <h2 class="mt-1 text-xl font-bold text-slate-100">
                            Nächste Module
                        </h2>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-semibold text-slate-100">
                                Spielräume
                            </h3>

                            <span class="rounded-full bg-slate-800 px-2 py-1 text-xs text-slate-400">
                                bald
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Räume für offene und private Stechen-Runden.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-semibold text-slate-100">
                                Rangliste
                            </h3>

                            <span class="rounded-full bg-slate-800 px-2 py-1 text-xs text-slate-400">
                                bald
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Spielerstatistiken, Punkte und Platzierungen.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-semibold text-slate-100">
                                Turniere
                            </h3>

                            <span class="rounded-full bg-slate-800 px-2 py-1 text-xs text-slate-400">
                                geplant
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-slate-400">
                            Strukturierte Wettbewerbe mit festen Regeln.
                        </p>
                    </div>

                    <div class="rounded-xl border border-red-500/20 bg-red-500/10 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-semibold text-red-100">
                                Echtgeld / Wallet
                            </h3>

                            <span class="rounded-full bg-red-500/20 px-2 py-1 text-xs text-red-200">
                                deaktiviert
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-red-100/80">
                            Diese Funktionen bleiben bis zur rechtlichen und technischen Freigabe ausgeschaltet.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Schnellzugriffe -->
            <aside class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
                <p class="text-sm font-medium uppercase tracking-wide text-amber-400">
                    Schnellzugriffe
                </p>

                <h2 class="mt-1 text-xl font-bold text-slate-100">
                    Konto & Informationen
                </h2>

                <div class="mt-6 space-y-3">
                    <a
                        href="{{ route('profile.edit') }}"
                        class="block rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-amber-400/50 hover:text-amber-300"
                    >
                        Profil bearbeiten
                    </a>

                    <a
                        href="{{ url('/rules') }}"
                        class="block rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-amber-400/50 hover:text-amber-300"
                    >
                        Spielregeln ansehen
                    </a>

                    <a
                        href="{{ url('/') }}"
                        class="block rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-amber-400/50 hover:text-amber-300"
                    >
                        Zur Startseite
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            type="submit"
                            class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-left text-sm font-medium text-slate-200 transition hover:border-red-400/50 hover:text-red-300"
                        >
                            Logout
                        </button>
                    </form>
                </div>
            </aside>
        </section>

        <!-- Rechtlicher Hinweis -->
        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            <h2 class="text-lg font-bold text-slate-100">
                Hinweis zur aktuellen Version
            </h2>

            <p class="mt-2 text-sm leading-6 text-slate-400">
                Dieses Dashboard dient zunächst als Account- und Entwicklungsübersicht.
                Spielmechaniken, Wirtschaftssysteme, Wallets und Echtgeld-Funktionen werden erst nach
                separater technischer, rechtlicher und sicherheitsbezogener Prüfung aktiviert.
            </p>

            <div class="mt-4 flex flex-wrap gap-3 text-sm">
                <a href="{{ url('/terms') }}" class="font-medium text-amber-300 underline-offset-4 hover:text-amber-200 hover:underline">
                    AGB
                </a>

                <a href="{{ url('/privacy') }}" class="font-medium text-amber-300 underline-offset-4 hover:text-amber-200 hover:underline">
                    Datenschutz
                </a>

                <a href="{{ url('/rules') }}" class="font-medium text-amber-300 underline-offset-4 hover:text-amber-200 hover:underline">
                    Spielregeln
                </a>
            </div>
        </section>
        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
            <p class="text-sm font-medium uppercase tracking-wide text-amber-400">
                Accountstatus
            </p>

            <div class="mt-4 grid gap-4 md:grid-cols-4">
                <div>
                    <p class="text-sm text-slate-400">Status</p>
                    <p class="mt-1 font-semibold text-slate-100">{{ Auth::user()->accountDisplayRole() }}</p>
                </div>

                <div>
                    <p class="text-sm text-slate-400">VIP</p>
                    <p class="mt-1 font-semibold text-slate-100">{{ Auth::user()->isVip() ? 'Aktiv' : 'Inaktiv' }}</p>
                </div>

                <div>
                    <p class="text-sm text-slate-400">Spielberechtigung</p>
                    <p class="mt-1 font-semibold text-slate-100">{{ Auth::user()->canPlayGame() ? 'Aktiv' : 'Nicht gesetzt' }}</p>
                </div>

                <div>
                    <p class="text-sm text-slate-400">Adminzugang</p>
                    <p class="mt-1 font-semibold text-slate-100">{{ Auth::user()->hasPermission('admin.access') ? 'Aktiv' : 'Nein' }}</p>
                </div>
            </div>
        </section>

    </div>
</x-app-layout>

