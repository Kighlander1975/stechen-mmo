@php
    $roomSupplyTestModeActive = (bool) ($roomSupplyTestMode['active'] ?? false);
    $roomSupplyTestModeExpiry = $roomSupplyTestMode['expiry'] ?? null;
@endphp

<section class="rounded-2xl border border-cyan-500/20 bg-cyan-500/10 p-6 shadow-xl shadow-black/20">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-cyan-300">
                Spielräume
            </p>

            <h2 class="mt-2 text-xl font-black text-slate-100">
                Room-Supply-Testmodus
            </h2>

            <p class="mt-3 max-w-3xl text-sm leading-6 text-cyan-100/80">
                Erlaubt im lokalen Entwicklungsmodus zeitlich begrenzt die Erzeugung von Sit'n'Go-Räumen
                ohne passende Wallet-Verteilung. In Production bleibt dieser Modus wirkungslos.
            </p>

            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 font-bold uppercase tracking-wide text-slate-300">
                    Umgebung: {{ $roomSupplyTestMode['environment'] }}
                </span>

                @if ($roomSupplyTestModeActive)
                    <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 font-bold uppercase tracking-wide text-emerald-200">
                        Aktiv bis {{ $roomSupplyTestModeExpiry->format('d.m.Y H:i') }}
                    </span>
                @else
                    <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 font-bold uppercase tracking-wide text-slate-400">
                        Inaktiv
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row lg:flex-col xl:flex-row">
            <form method="POST" action="{{ $roomSupplyTestMode['enableUrl'] }}">
                @csrf

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg border border-cyan-300/40 bg-cyan-400 px-4 py-2 text-sm font-black uppercase tracking-wide text-slate-950 transition hover:bg-cyan-300"
                >
                    60 Minuten aktivieren
                </button>
            </form>

            <form method="POST" action="{{ $roomSupplyTestMode['disableUrl'] }}">
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
