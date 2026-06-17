<x-app-layout
    header-eyebrow="Administration"
    header-title="Admin-Dashboard"
    header-status-label="Zugriff über Permission: admin.access"
    header-status-tone="admin"
>

    <div class="space-y-8">
        <section class="rounded-2xl border border-red-500/20 bg-red-500/10 p-6 shadow-xl shadow-black/20">
            <h2 class="text-lg font-bold text-red-200">
                Geschützter Administrationsbereich
            </h2>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-red-100/80">
                Dieser Bereich ist nur für Konten sichtbar, die die Permission
                <strong>admin.access</strong> besitzen. Staff-Rolle, Player-Tier und
                konkrete Berechtigungen werden getrennt behandelt.
            </p>
        </section>

        <a
            href="{{ route('admin.rewards.registration-bonus-backfill.index') }}"
            class="block rounded-2xl border border-amber-500/20 bg-amber-500/10 p-6 shadow-xl shadow-black/20 transition hover:border-amber-400/50 hover:bg-amber-500/15"
        >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wide text-amber-300">
                        Rewards
                    </p>

                    <h2 class="mt-2 text-xl font-black text-slate-100">
                        Startguthaben-Backfill
                    </h2>

                    <p class="mt-3 max-w-3xl text-sm leading-6 text-amber-100/80">
                        Offene Accounts ohne Registration-Bonus anzeigen. Einzel- und Bulk-Aktionen werden in den nächsten Schritten ergänzt.
                    </p>
                </div>

                <span class="inline-flex items-center justify-center rounded-lg border border-amber-300/40 bg-slate-950/60 px-4 py-2 text-sm font-bold text-amber-200">
                    Backfill öffnen
                </span>
            </div>
        </a>

        <section class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
                <p class="text-sm font-medium text-slate-400">
                    Benutzer
                </p>

                <h3 class="mt-2 text-2xl font-bold text-slate-100">
                    Verwaltung
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-400">
                    Später: Spieler suchen, Rollen ändern, Konten prüfen und sperren.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
                <p class="text-sm font-medium text-slate-400">
                    Spielbetrieb
                </p>

                <h3 class="mt-2 text-2xl font-bold text-slate-100">
                    Kontrolle
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-400">
                    Später: Spielräume, Chaträume, Runden, Turniere und Regelsets administrieren.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
                <p class="text-sm font-medium text-slate-400">
                    System
                </p>

                <h3 class="mt-2 text-2xl font-bold text-slate-100">
                    Status
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-400">
                    Später: Logs, Jobs, Wartungsmodus und technische Prüfungen.
                </p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
            <p class="text-sm font-medium uppercase tracking-wide text-amber-400">
                Aktueller Account
            </p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-sm text-slate-400">Name</p>
                    <p class="mt-1 font-semibold text-slate-100">{{ Auth::user()->name }}</p>
                </div>

                <div>
                    <p class="text-sm text-slate-400">E-Mail</p>
                    <p class="mt-1 font-semibold text-slate-100">{{ Auth::user()->email }}</p>
                </div>

                <div>
                    <p class="text-sm text-slate-400">Account</p>
                    <p class="mt-1 font-semibold text-slate-100">{{ Auth::user()->accountDisplayRole() }}</p>
                </div>

                <div>
                    <p class="text-sm text-slate-400">Spielberechtigung</p>
                    <p class="mt-1 font-semibold text-slate-100">
                        {{ Auth::user()->canPlayGame() ? 'Aktiv' : 'Nicht gesetzt' }}
                    </p>
                </div>
            </div>

            <div class="mt-6">
                <p class="text-sm text-slate-400">Permissions</p>

                <div class="mt-2 flex flex-wrap gap-2">
                    @forelse (Auth::user()->permissions ?? [] as $permission)
                        <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-xs font-medium text-slate-300">
                            {{ $permission }}
                        </span>
                    @empty
                        <span class="text-sm text-slate-500">Keine Permissions gesetzt.</span>
                    @endforelse
                </div>
            </div>

            <div class="mt-6">
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-700 bg-slate-950/70 px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-amber-400/50 hover:text-amber-300"
                >
                    Zurück zum Spielerkonto
                </a>
            </div>
        </section>
    </div>
</x-app-layout>
