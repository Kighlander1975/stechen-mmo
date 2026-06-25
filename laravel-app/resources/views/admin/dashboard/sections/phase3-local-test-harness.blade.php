@php
    $phase3HarnessEnabled = (bool) ($phase3LocalTestHarness['enabled'] ?? false);
    $phase3HarnessAvailable = (bool) ($phase3LocalTestHarness['available'] ?? false);
@endphp

<section class="rounded-2xl border border-violet-500/20 bg-violet-500/10 p-6 shadow-xl shadow-black/20">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-violet-300">
                Phase 3 Entwicklung
            </p>

            <h2 class="mt-2 text-xl font-black text-slate-100">
                Lokaler Phase-3-Browser-Testmodus
            </h2>

            <p class="mt-3 max-w-3xl text-sm leading-6 text-violet-100/80">
                Aktiviert eine lokale Browser-Testumgebung für die Entwicklung der echten Live-Funktionen:
                Raum betreten, Buy-in reservieren, Raum verlassen, Startphase, Raumstart sowie Cancel und Reset von Testzuständen.
                In Production bleibt dieser Modus wirkungslos.
            </p>

            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 font-bold uppercase tracking-wide text-slate-300">
                    Umgebung: {{ $phase3LocalTestHarness['environment'] }}
                </span>

                <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 font-bold uppercase tracking-wide text-slate-300">
                    Testuser-Domain: {{ $phase3LocalTestHarness['testUserEmailDomain'] }}
                </span>

                @if (! $phase3HarnessAvailable)
                    <span class="rounded-full border border-red-400/30 bg-red-400/10 px-3 py-1 font-bold uppercase tracking-wide text-red-200">
                        Nicht verfügbar
                    </span>
                @elseif ($phase3HarnessEnabled)
                    <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 font-bold uppercase tracking-wide text-emerald-200">
                        Aktiv
                    </span>
                @else
                    <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 font-bold uppercase tracking-wide text-slate-400">
                        Inaktiv
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row lg:flex-col xl:flex-row">
            <form method="POST" action="{{ $phase3LocalTestHarness['enableUrl'] }}">
                @csrf

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg border border-violet-300/40 bg-violet-400 px-4 py-2 text-sm font-black uppercase tracking-wide text-slate-950 transition hover:bg-violet-300 disabled:cursor-not-allowed disabled:opacity-50"
                    @disabled(! $phase3HarnessAvailable)
                >
                    Aktivieren
                </button>
            </form>

            <form method="POST" action="{{ $phase3LocalTestHarness['disableUrl'] }}">
                @csrf

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-700 bg-slate-950/70 px-4 py-2 text-sm font-bold text-slate-200 transition hover:border-red-400/50 hover:text-red-300"
                >
                    Deaktivieren
                </button>
            </form>
        </div>
    </div>
</section>
