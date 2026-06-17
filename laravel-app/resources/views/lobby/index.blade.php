@php
    use App\Models\GameRoom;
@endphp

<x-app-layout
    header-eyebrow="SPIELLOBBY"
    header-title="Lobby"
    header-status-label="Verifiziert"
    header-status-tone="success"
>
    @section('title', 'Lobby - '.config('app.name', 'Stechen-MMO'))

    <div class="space-y-8">
        <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-6 shadow-2xl shadow-black/20">
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-amber-300">
                        Räume finden
                    </p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-100">
                        Spielräume
                    </h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-400">
                        Wähle passende Räume nach Status, Startmodus, Buy-in und Tischgröße.
                        Beitritt, Reservierung und Spielstart werden in den nächsten Schritten ergänzt.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-700 bg-slate-950/60 px-4 py-3 text-sm text-slate-300">
                    <span class="font-semibold text-slate-100">{{ $gameRooms->count() }}</span>
                    Räume gefunden
                </div>
            </div>

            <form method="GET" action="{{ route('lobby') }}" class="mt-6 grid gap-4 md:grid-cols-4">
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Status</span>
                    <select name="status" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">Alle</option>
                        <option value="{{ GameRoom::STATUS_OPEN }}" @selected(($filters['status'] ?? null) === GameRoom::STATUS_OPEN)>Offen</option>
                        <option value="{{ GameRoom::STATUS_FULL }}" @selected(($filters['status'] ?? null) === GameRoom::STATUS_FULL)>Voll</option>
                        <option value="{{ GameRoom::STATUS_RUNNING }}" @selected(($filters['status'] ?? null) === GameRoom::STATUS_RUNNING)>Läuft</option>
                        <option value="{{ GameRoom::STATUS_FINISHED }}" @selected(($filters['status'] ?? null) === GameRoom::STATUS_FINISHED)>Beendet</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Startmodus</span>
                    <select name="start_mode" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">Alle</option>
                        <option value="{{ GameRoom::START_MODE_WHEN_FULL }}" @selected(($filters['start_mode'] ?? null) === GameRoom::START_MODE_WHEN_FULL)>Wenn voll</option>
                        <option value="{{ GameRoom::START_MODE_SCHEDULED }}" @selected(($filters['start_mode'] ?? null) === GameRoom::START_MODE_SCHEDULED)>Geplant</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Buy-in</span>
                    <select name="buy_in" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">Alle</option>
                        <option value="free" @selected(($filters['buy_in'] ?? null) === 'free')>Kostenlos</option>
                        <option value="micro" @selected(($filters['buy_in'] ?? null) === 'micro')>Mikro bis 500 St$</option>
                        <option value="low" @selected(($filters['buy_in'] ?? null) === 'low')>Low bis 2.000 St$</option>
                        <option value="medium" @selected(($filters['buy_in'] ?? null) === 'medium')>Medium bis 10.000 St$</option>
                        <option value="high" @selected(($filters['buy_in'] ?? null) === 'high')>High ab 10.001 St$</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Tischgröße</span>
                    <select name="players" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100">
                        <option value="">Alle</option>
                        <option value="heads_up" @selected(($filters['players'] ?? null) === 'heads_up')>2 Spieler</option>
                        <option value="small" @selected(($filters['players'] ?? null) === 'small')>3-4 Spieler</option>
                        <option value="medium" @selected(($filters['players'] ?? null) === 'medium')>5-6 Spieler</option>
                        <option value="large" @selected(($filters['players'] ?? null) === 'large')>7-11 Spieler</option>
                    </select>
                </label>

                <div class="flex gap-3 md:col-span-4">
                    <button type="submit" class="rounded-xl bg-amber-400 px-4 py-2 text-sm font-bold text-slate-950 transition hover:bg-amber-300">
                        Filter anwenden
                    </button>

                    <a href="{{ route('lobby') }}" class="rounded-xl border border-slate-700 px-4 py-2 text-sm font-bold text-slate-300 transition hover:border-slate-500 hover:text-slate-100">
                        Zurücksetzen
                    </a>
                </div>
            </form>
        </section>

        <section class="grid gap-4">
            @forelse ($gameRooms as $room)
                <article class="rounded-3xl border border-slate-800 bg-slate-900/70 p-5 shadow-xl shadow-black/10">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-xl font-black tracking-tight text-slate-100">
                                    {{ $room->name }}
                                </h3>

                                <span class="rounded-full border border-slate-700 bg-slate-950/70 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-slate-400">
                                    {{ $room->public_code }}
                                </span>

                                <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-emerald-300">
                                    {{ match ($room->status) {
                                        GameRoom::STATUS_OPEN => 'Offen',
                                        GameRoom::STATUS_FULL => 'Voll',
                                        GameRoom::STATUS_RUNNING => 'Läuft',
                                        GameRoom::STATUS_FINISHED => 'Beendet',
                                        default => $room->status,
                                    } }}
                                </span>
                            </div>

                            <dl class="mt-4 grid gap-3 text-sm text-slate-300 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Buy-in</dt>
                                    <dd class="mt-1 font-semibold">{{ number_format($room->buy_in_units, 0, ',', '.') }} St$</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Spieler</dt>
                                    <dd class="mt-1 font-semibold">{{ $room->active_players_count }} / {{ $room->max_players }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Startmodus</dt>
                                    <dd class="mt-1 font-semibold">
                                        {{ $room->isScheduled() ? 'Geplant' : 'Wenn voll' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Rake</dt>
                                    <dd class="mt-1 font-semibold">{{ number_format($room->rake_basis_points / 100, 2, ',', '.') }} %</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-2xl border border-slate-700 bg-slate-950/60 px-4 py-3 text-sm text-slate-400 lg:min-w-52">
                            <p class="font-semibold text-slate-200">Beitritt vorbereitet</p>
                            <p class="mt-1 text-xs leading-5">
                                Join, Buy-in-Reserve und Leave folgen als separater Service-Schritt.
                            </p>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
                    <h3 class="text-lg font-black text-slate-100">Keine Räume gefunden</h3>
                    <p class="mt-2 text-sm text-slate-400">
                        Passe die Filter an oder erstelle später über die Admin-/Raumverwaltung neue Räume.
                    </p>
                </div>
            @endforelse
        </section>
    </div>
</x-app-layout>
