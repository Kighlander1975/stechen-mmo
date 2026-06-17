<x-app-layout
    header-eyebrow="Administration"
    header-title="Startguthaben-Backfill"
    header-status-label="Nur verifizierte offene Accounts können später abgefertigt werden"
    header-status-tone="admin"
>
    <div class="space-y-8">
        <section class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-6 shadow-xl shadow-black/20">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wide text-amber-300">
                        Registration-Bonus Backfill
                    </p>

                    <h2 class="mt-2 text-2xl font-black text-slate-100">
                        Offene Startguthaben prüfen
                    </h2>

                    <p class="mt-3 max-w-3xl text-sm leading-6 text-amber-100/80">
                        Diese Ansicht zeigt alle Accounts ohne Registration-Bonus-Claim. Schreibaktionen werden
                        bewusst nur für bestätigte E-Mail-Adressen zugelassen. Verifizierte Accounts können einzeln
                        abgefertigt werden; Bulk-Aktionen folgen separat.
                    </p>
                </div>

                <a
                    href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-700 bg-slate-950/70 px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-amber-400/50 hover:text-amber-300"
                >
                    Zurück zum Admin-Dashboard
                </a>
            </div>
        </section>

        <section class="grid gap-6 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
                <p class="text-sm font-medium text-slate-400">Offene Accounts</p>
                <p class="mt-2 text-3xl font-black text-slate-100">{{ $openUsers->count() }}</p>
            </div>

            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-6 shadow-xl shadow-black/20">
                <p class="text-sm font-medium text-emerald-200">Bereit zur Abfertigung</p>
                <p class="mt-2 text-3xl font-black text-emerald-100">{{ $verifiedOpenUsersCount }}</p>
            </div>

            <div class="rounded-2xl border border-red-500/20 bg-red-500/10 p-6 shadow-xl shadow-black/20">
                <p class="text-sm font-medium text-red-200">E-Mail noch offen</p>
                <p class="mt-2 text-3xl font-black text-red-100">{{ $unverifiedOpenUsersCount }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-100">
                        Offene Accounts
                    </h2>

                    <p class="mt-1 text-sm text-slate-400">
                        Alle Einträge ohne Registration-Bonus-Claim. Bereits versorgte Accounts werden hier nicht angezeigt.
                    </p>
                </div>

                <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-200">
                    Einzelaktion C2
                </span>
            </div>

            @if ($openUsers->isEmpty())
                <div class="mt-6 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-100">
                    Es gibt aktuell keine offenen Accounts ohne Startguthaben.
                </div>
            @else
                <div class="mt-6 overflow-hidden rounded-xl border border-slate-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-800">
                            <thead class="bg-slate-950/70">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-400">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-400">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-400">E-Mail</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-400">Registriert</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-400">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-400">Aktionen</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-800 bg-slate-900/60">
                                @foreach ($openUsers as $user)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-slate-300">
                                            #{{ $user->id }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-100">
                                            {{ $user->name }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-300">
                                            {{ $user->email }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-400">
                                            {{ optional($user->created_at)->format('d.m.Y H:i') }}
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                                            @if ($user->hasVerifiedEmail())
                                                <span class="inline-flex rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-200">
                                                    Bereit
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full border border-red-400/30 bg-red-400/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-red-200">
                                                    E-Mail offen
                                                </span>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                                            @if ($user->hasVerifiedEmail())
                                                <form method="POST" action="{{ route('admin.rewards.registration-bonus-backfill.user', $user) }}">
                                                    @csrf

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg border border-emerald-300/40 bg-emerald-400 px-3 py-2 text-xs font-black uppercase tracking-wide text-slate-950 transition hover:bg-emerald-300"
                                                    >
                                                        Startguthaben einrichten
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-sm text-slate-500">
                                                    Nicht möglich – E-Mail offen
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
