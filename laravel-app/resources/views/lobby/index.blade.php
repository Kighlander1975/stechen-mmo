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

    @php
        $activeLobbyTab = request('tab') === 'field' ? 'field' : 'rooms';
    @endphp

    <div class="flex h-[calc(100vh-285px)] min-h-[520px] flex-col gap-3 overflow-hidden">
        <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-[2rem] border border-slate-800 bg-slate-950/30 p-3 shadow-2xl shadow-black/30">
            <nav class="flex flex-wrap items-center gap-2 px-1 pb-2" aria-label="Lobby-Bereiche">
                <a href="{{ route('lobby', request()->except('tab')) }}" class="{{ $activeLobbyTab === 'rooms' ? 'bg-amber-400 text-slate-950 shadow-lg shadow-amber-950/20' : 'border border-slate-700 bg-slate-950/60 text-slate-500 hover:border-slate-600 hover:text-slate-300' }} inline-flex items-center rounded-2xl px-4 py-2 text-sm font-black uppercase tracking-wide transition">
                    Räume
                </a>

                <button type="button" disabled class="{{ $activeLobbyTab === 'field' ? 'bg-amber-400/80 text-slate-950 shadow-lg shadow-amber-950/20' : 'border border-slate-800 bg-slate-950/40 text-slate-600' }} inline-flex cursor-not-allowed items-center gap-2 rounded-2xl px-4 py-2 text-sm font-black uppercase tracking-wide opacity-75 transition" title="Spielfeld-Prototyp ist aktuell nur per direkter Test-URL erreichbar.">
                    Spielfeld
                    <span class="{{ $activeLobbyTab === 'field' ? 'border-slate-950/20 text-slate-800' : 'border-slate-800 text-slate-600' }} rounded-full border px-2 py-0.5 text-[0.65rem] font-black normal-case tracking-normal">
                        Test
                    </span>
                </button>
            </nav>

            @if ($activeLobbyTab === 'field')
                <section class="grid min-h-[64vh] flex-1 gap-5 pt-5 xl:grid-cols-5">
                    <div class="grid min-h-[64vh] gap-5 xl:col-span-4 xl:grid-rows-[6fr_1fr]">
                        <article class="relative overflow-hidden rounded-[1.5rem]">
                            @php
                                $fieldSeatCount = (int) request('seats', 4);
                                $fieldSeatCount = max(2, min(11, $fieldSeatCount));

                                $heroSectorDegrees = 70;
                                $heroCenterAngle = 270;
                                $heroStartAngle = $heroCenterAngle - ($heroSectorDegrees / 2);
                                $heroEndAngle = $heroCenterAngle + ($heroSectorDegrees / 2);

                                $opponentCount = $fieldSeatCount - 1;
                                $nonHeroArcDegrees = 360 - $heroSectorDegrees;
                                $nonHeroArcStartAngle = $heroEndAngle;

                                /*
                                 * Prototyp:
                                 * Spaeter kommt dieser Wert aus dem Spielzustand.
                                 */
                                $heroSeatNumber = 1;
                                $activeSeatNumber = $fieldSeatCount === 4 ? $heroSeatNumber : 2;

                                $fieldSeatPositions = [];
                                $fieldSeatAngles = [];
                                $fieldSeatSectorRanges = [
                                    $heroSeatNumber => [
                                        'start' => $heroStartAngle,
                                        'end' => $heroEndAngle,
                                        'size' => $heroSectorDegrees,
                                    ],
                                ];

                                /*
                                 * Winkel-System:
                                 * 0 Grad = rechts, 90 Grad = oben, 180 Grad = links, 270 Grad = unten.
                                 *
                                 * Hero:
                                 * - sitzt unten
                                 * - bekommt fix ca. 70 Grad
                                 *
                                 * Non-Hero:
                                 * - bei 2 Spielern: Gegner bekommt oben ebenfalls ca. 70 Grad
                                 * - ab 3 Spielern: Gegner teilen die restlichen 290 Grad gleichmaessig
                                 * - Spielerkarte sitzt immer in der Mitte ihres Sektors
                                 */
                                if ($opponentCount === 1) {
                                    $fieldSeatAngles[2] = 90;

                                    $fieldSeatSectorRanges[2] = [
                                        'start' => 90 - ($heroSectorDegrees / 2),
                                        'end' => 90 + ($heroSectorDegrees / 2),
                                        'size' => $heroSectorDegrees,
                                    ];

                                } else {
                                    $nonHeroSectorDegrees = $nonHeroArcDegrees / $opponentCount;
                                    for ($seatNumber = 2; $seatNumber <= $fieldSeatCount; $seatNumber++) {
                                        $index = $seatNumber - 2;
                                        $clockwiseIndex = ($opponentCount - 1) - $index;

                                        $sectorStart = $nonHeroArcStartAngle + ($nonHeroSectorDegrees * $clockwiseIndex);
                                        $sectorEnd = $sectorStart + $nonHeroSectorDegrees;
                                        $seatAngle = $sectorStart + ($nonHeroSectorDegrees / 2);

                                        /*
                                         * Bis 6 Gesamtspieler bleiben die sichtbaren aktiven Spieler-Sektoren
                                         * bewusst kleiner, damit leere Tischbereiche entstehen duerfen.
                                         * Ab 7 Gesamtspieler wird der komplette Non-Hero-Bogen genutzt.
                                         */
                                        $visualSectorDegrees = $fieldSeatCount < 7
                                            ? min(60, $nonHeroSectorDegrees)
                                            : $nonHeroSectorDegrees;

                                        $visualSectorStart = $seatAngle - ($visualSectorDegrees / 2);
                                        $visualSectorEnd = $seatAngle + ($visualSectorDegrees / 2);

                                        $fieldSeatAngles[$seatNumber] = round(fmod($seatAngle + 360, 360), 2);

                                        $fieldSeatSectorRanges[$seatNumber] = [
                                            'start' => round(fmod($visualSectorStart + 360, 360), 2),
                                            'end' => round(fmod($visualSectorEnd + 360, 360), 2),
                                            'size' => round($visualSectorDegrees, 3),
                                        ];
                                    }
                                }

                                foreach ($fieldSeatAngles as $seatNumber => $seatAngle) {
                                    $radians = deg2rad($seatAngle);

                                    $left = 50 + (43.5 * cos($radians));
                                    $top = 51 - (39.5 * sin($radians));

                                    $fieldSeatPositions[$seatNumber] = [
                                        'left' => round($left, 2),
                                        'top' => round($top, 2),
                                    ];
                                }

                                $activeSector = $fieldSeatSectorRanges[$activeSeatNumber] ?? null;
                                $activeSectorCssStartAngle = null;
                                $activeSectorCssSize = null;

                                if ($activeSector !== null) {
                                    /*
                                     * CSS conic-gradient:
                                     * 0 Grad zeigt nach oben und laeuft im Uhrzeigersinn.
                                     *
                                     * Unser Spielwinkel:
                                     * 0 Grad = rechts, 90 Grad = oben, 180 Grad = links, 270 Grad = unten.
                                     *
                                     * Umrechnung:
                                     * cssAngle = 90 - gameAngle
                                     */
                                    $activeSectorCssStartAngle = round(fmod((90 - $activeSector['end']) + 360, 360), 3);
                                    $activeSectorCssSize = $activeSector['size'];
                                }
                            @endphp

                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,191,36,0.08),transparent_42%)]"></div>

                            <div class="relative h-full min-h-[46vh] rounded-[1.5rem] border border-slate-800 bg-slate-950/50 shadow-2xl shadow-black/20">


                                @if ($activeSectorCssStartAngle !== null && $activeSectorCssSize !== null)
                                    <div
                                        class="pointer-events-none absolute inset-0 rounded-[1.5rem] opacity-35"
                                        style="background:
                                            conic-gradient(from {{ $activeSectorCssStartAngle }}deg at 50% 51%,
                                                rgba(148, 163, 184, 0.16) 0deg,
                                                rgba(148, 163, 184, 0.16) {{ $activeSectorCssSize }}deg,
                                                rgba(148, 163, 184, 0.00) {{ $activeSectorCssSize }}deg,
                                                rgba(148, 163, 184, 0.00) 360deg);"
                                    ></div>
                                @endif

                                @for ($seatNumber = 2; $seatNumber <= $fieldSeatCount; $seatNumber++)
                                    <div
                                        class="absolute flex h-14 w-24 -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center rounded-2xl border border-slate-700 bg-slate-950/90 text-center shadow-xl shadow-black/20"
                                        style="left: {{ $fieldSeatPositions[$seatNumber]['left'] }}%; top: {{ $fieldSeatPositions[$seatNumber]['top'] }}%;"
                                    >
                                        <p class="text-[0.6rem] font-black uppercase leading-none tracking-wide text-slate-500">
                                            Spieler {{ $seatNumber }}
                                        </p>
                                        <p class="mt-1 text-sm font-bold leading-none text-slate-300">
                                            Platz {{ $seatNumber }}
                                        </p>
                                    </div>
                                @endfor

                                <div class="absolute left-1/2 top-1/2 flex h-56 w-56 max-w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-[2rem] border border-amber-400/20 bg-slate-900/90 shadow-2xl shadow-black/30">
                                    <div class="text-center">
                                        <p class="text-xs font-black uppercase tracking-wide text-amber-300">
                                            Tischmitte
                                        </p>
                                        <p class="mt-2 text-sm leading-6 text-slate-400">
                                            Stich, Ablage und Rundenzustand
                                        </p>
                                    </div>
                                </div>

                                <div class="absolute bottom-4 left-1/2 w-[min(34rem,calc(100%-2rem))] -translate-x-1/2 rounded-[1.25rem] border border-amber-400/40 bg-amber-400/10 px-5 py-3 text-center shadow-xl shadow-black/20">
                                    <p class="text-[0.65rem] font-black uppercase tracking-wide text-amber-300">
                                        Eigener Sektor
                                    </p>
                                    <p class="mt-1 text-sm font-bold text-slate-100">
                                        Du sitzt immer unten am Tisch
                                    </p>
                                </div>
                            </div>
                        </article>

                        <aside class="rounded-[1.5rem] border border-slate-800 bg-slate-900/70 p-5 shadow-2xl shadow-black/20">
                            <div class="flex h-full flex-col justify-between gap-4 md:flex-row md:items-center">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-amber-300">
                                        Aktionen
                                    </p>
                                    <h2 class="mt-1 text-xl font-black tracking-tight text-slate-100">
                                        Aktionsleiste
                                    </h2>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">
                                        Hier erscheinen später kontextabhängige Spielaktionen.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="cursor-not-allowed rounded-xl border border-slate-700 px-4 py-2 text-sm font-bold text-slate-500 opacity-70" disabled>
                                        Passen
                                    </button>

                                    <button type="button" class="cursor-not-allowed rounded-xl border border-slate-700 px-4 py-2 text-sm font-bold text-slate-500 opacity-70" disabled>
                                        Karte spielen
                                    </button>

                                    <button type="button" class="cursor-not-allowed rounded-xl bg-amber-400 px-4 py-2 text-sm font-black text-slate-950 opacity-50" disabled>
                                        Bestätigen
                                    </button>
                                </div>
                            </div>
                        </aside>
                    </div>

                    <aside class="flex min-h-[64vh] flex-col rounded-[1.5rem] border border-slate-800 bg-slate-900/70 p-5 shadow-2xl shadow-black/20 xl:col-span-1">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-amber-300">
                                Tisch-Chat
                            </p>
                        </div>

                        <div class="mt-3 min-h-0 flex-1 overflow-y-auto rounded-2xl border border-slate-800 bg-slate-950/40 p-3">
                            <div class="flex min-h-full flex-col justify-end gap-3 text-sm">
                                <div class="max-w-[85%] rounded-2xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-slate-300">
                                    <p class="text-[0.65rem] font-black uppercase tracking-wide text-slate-500">System</p>
                                    <p class="mt-1 leading-5">Spieler 4 ist dem Tisch beigetreten.</p>
                                </div>

                                <div class="max-w-[85%] rounded-2xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-slate-300">
                                    <p class="text-[0.65rem] font-black uppercase tracking-wide text-slate-500">Spieler 2</p>
                                    <p class="mt-1 leading-5">Bereit für die nächste Runde.</p>
                                </div>

                                <div class="ml-auto max-w-[85%] rounded-2xl border border-amber-400/30 bg-amber-400/10 px-3 py-2 text-right text-slate-100">
                                    <p class="text-[0.65rem] font-black uppercase tracking-wide text-amber-300">Du</p>
                                    <p class="mt-1 leading-5">Ich bin dabei.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 shrink-0 rounded-2xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-500">
                            &gt; Tisch-Nachricht...
                        </div>
                    </aside>
                </section>
            @else
                <div class="flex min-h-0 flex-1 flex-col gap-3 pt-3">

                @php
                    $lobbyRoomBrowserJson = json_encode(
                        $lobbyRoomBrowserProps,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                    );
                @endphp

                <div
                    data-vue-component="lobby-room-browser"
                    data-props='{{ $lobbyRoomBrowserJson }}'
                ></div>

        <section class="grid h-[250px] shrink-0 items-stretch gap-3 overflow-hidden xl:grid-cols-12">
            <article class="flex h-full min-h-0 flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70 p-4 shadow-2xl shadow-black/20 xl:col-span-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-300">
                            Räume finden
                        </p>
                        <h2 class="mt-1 text-xl font-black tracking-tight text-slate-100">
                            Spielräume filtern
                        </h2>
                    </div>

                    <div class="shrink-0 rounded-2xl border border-slate-700 bg-slate-950/60 px-3 py-2 text-right text-xs text-slate-400">
                        <span class="block text-lg font-black leading-none text-slate-100">{{ $gameRooms->count() }}</span>
                        Räume
                    </div>
                </div>

                <p class="mt-2 text-sm leading-5 text-slate-400">
                    Wähle passende Räume nach Status, Startmodus, Buy-in und Tischgröße.
                </p>

                <form method="GET" action="{{ route('lobby') }}" class="mt-3 flex flex-1 flex-col justify-between gap-3">
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                        <label class="block">
                            <span class="text-[0.65rem] font-black uppercase tracking-wide text-slate-500">Status</span>
                            <select name="status" class="mt-1.5 h-9 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 text-sm text-slate-100">
                                <option value="">Alle</option>
                                <option value="{{ GameRoom::STATUS_OPEN }}" @selected(($filters['status'] ?? null) === GameRoom::STATUS_OPEN)>Offen</option>
                                <option value="{{ GameRoom::STATUS_FULL }}" @selected(($filters['status'] ?? null) === GameRoom::STATUS_FULL)>Voll</option>
                                <option value="{{ GameRoom::STATUS_RUNNING }}" @selected(($filters['status'] ?? null) === GameRoom::STATUS_RUNNING)>Läuft</option>
                                <option value="{{ GameRoom::STATUS_FINISHED }}" @selected(($filters['status'] ?? null) === GameRoom::STATUS_FINISHED)>Beendet</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-[0.65rem] font-black uppercase tracking-wide text-slate-500">Startmodus</span>
                            <select name="start_mode" class="mt-1.5 h-9 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 text-sm text-slate-100">
                                <option value="">Alle</option>
                                <option value="{{ GameRoom::START_MODE_WHEN_FULL }}" @selected(($filters['start_mode'] ?? null) === GameRoom::START_MODE_WHEN_FULL)>Wenn voll</option>
                                <option value="{{ GameRoom::START_MODE_SCHEDULED }}" @selected(($filters['start_mode'] ?? null) === GameRoom::START_MODE_SCHEDULED)>Geplant</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-[0.65rem] font-black uppercase tracking-wide text-slate-500">Buy-in</span>
                            <select name="buy_in" class="mt-1.5 h-9 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 text-sm text-slate-100">
                                <option value="">Alle</option>
                                <option value="free" @selected(($filters['buy_in'] ?? null) === 'free')>Kostenlos</option>
                                <option value="micro" @selected(($filters['buy_in'] ?? null) === 'micro')>Mikro bis 500 St$</option>
                                <option value="low" @selected(($filters['buy_in'] ?? null) === 'low')>Low bis 2.000 St$</option>
                                <option value="medium" @selected(($filters['buy_in'] ?? null) === 'medium')>Medium bis 10.000 St$</option>
                                <option value="high" @selected(($filters['buy_in'] ?? null) === 'high')>High ab 10.001 St$</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-[0.65rem] font-black uppercase tracking-wide text-slate-500">Tischgröße</span>
                            <select name="players" class="mt-1.5 h-9 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 text-sm text-slate-100">
                                <option value="">Alle</option>
                                <option value="heads_up" @selected(($filters['players'] ?? null) === 'heads_up')>2 Spieler</option>
                                <option value="small" @selected(($filters['players'] ?? null) === 'small')>3-4 Spieler</option>
                                <option value="medium" @selected(($filters['players'] ?? null) === 'medium')>5-6 Spieler</option>
                                <option value="large" @selected(($filters['players'] ?? null) === 'large')>7-11 Spieler</option>
                            </select>
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="rounded-xl bg-amber-400 px-4 py-2 text-sm font-black text-slate-950 transition hover:bg-amber-300">
                            Filter anwenden
                        </button>

                        <a href="{{ route('lobby') }}" class="rounded-xl border border-slate-700 px-4 py-2 text-sm font-bold text-slate-300 transition hover:border-slate-500 hover:text-slate-100">
                            Zurücksetzen
                        </a>
                    </div>
                </form>
            </article>

            <aside class="flex h-full min-h-0 flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70 p-4 shadow-2xl shadow-black/20 xl:col-span-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-300">
                            Rauminformationen
                        </p>
                        <h2 class="mt-1 truncate text-xl font-black tracking-tight text-slate-100">
                            {{ $selectedRoom ? $selectedRoom->name : 'Kein Raum ausgewählt' }}
                        </h2>

                        <p class="mt-1 truncate font-mono text-xs font-semibold text-slate-500">
                            {{ $selectedRoom ? $selectedRoom->public_code : '-' }}
                        </p>
                    </div>

                    <span class="rounded-full border {{ $selectedRoom ? 'border-amber-400/40 bg-amber-400/10 text-amber-200' : 'border-slate-700 bg-slate-950/70 text-slate-500' }} px-3 py-1 text-xs font-bold uppercase tracking-wide">
                        vorbereitet
                    </span>
                </div>

                <div class="mt-3 flex min-h-0 flex-1 flex-col justify-between overflow-hidden rounded-2xl border {{ $selectedRoom ? 'border-amber-400/30 bg-slate-950/60' : 'border-dashed border-slate-700 bg-slate-950/40' }} p-3">
                    @php
                        $detailBuyIn = $selectedRoom ? number_format($selectedRoom->buy_in_units, 0, ',', '.').' St$' : '-';
                        $detailPlayers = $selectedRoom ? $selectedRoom->active_players_count.' / '.$selectedRoom->max_players : '-';
                        $detailStart = $selectedRoom ? ($selectedRoom->isScheduled() ? 'Geplant' : 'Wenn voll') : '-';
                        $detailStatus = $selectedRoom
                            ? match ($selectedRoom->status) {
                                GameRoom::STATUS_OPEN => 'Offen',
                                GameRoom::STATUS_FULL => 'Voll',
                                GameRoom::STATUS_RUNNING => 'Läuft',
                                GameRoom::STATUS_FINISHED => 'Beendet',
                                default => $selectedRoom->status,
                            }
                            : '-';

                        $detailPrizePool = '-';
                        $detailFeeHint = 'Raum auswählen';

                        if ($selectedRoom) {
                            $selectedRoomGrossPoolUnits = $selectedRoom->buy_in_units * $selectedRoom->max_players;
                            $selectedRoomRakeUnits = intdiv($selectedRoomGrossPoolUnits * $selectedRoom->rake_basis_points, 10000);
                            $selectedRoomPrizePoolUnits = $selectedRoomGrossPoolUnits - $selectedRoomRakeUnits;

                            $detailPrizePool = number_format($selectedRoomPrizePoolUnits, 0, ',', '.').' St$';
                            $detailFeeHint = 'abzgl. '.number_format($selectedRoom->rake_basis_points / 100, 2, ',', '.').' % Gebühr';
                        }
                    @endphp

                    <div class="min-h-0 flex-1">
                        <dl class="grid h-full grid-cols-5 gap-2 text-sm">
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">Buy-in</dt>
                                <dd class="mt-1 truncate font-semibold leading-tight text-slate-300">{{ $detailBuyIn }}</dd>
                            </div>

                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">Spieler</dt>
                                <dd class="mt-1 truncate font-semibold leading-tight text-slate-300">{{ $detailPlayers }}</dd>
                            </div>

                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">Start</dt>
                                <dd class="mt-1 truncate font-semibold leading-tight text-slate-300">{{ $detailStart }}</dd>
                            </div>

                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">Status</dt>
                                <dd class="mt-1 truncate font-semibold leading-tight text-slate-300">{{ $detailStatus }}</dd>
                            </div>

                            <div class="rounded-xl border {{ $selectedRoom ? 'border-emerald-400/20 bg-emerald-400/10' : 'border-slate-800 bg-slate-950/50' }} px-3 py-2">
                                <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide {{ $selectedRoom ? 'text-emerald-300' : 'text-slate-500' }}">Gewinnpool</dt>
                                <dd class="mt-1 truncate font-black leading-tight {{ $selectedRoom ? 'text-emerald-100' : 'text-slate-300' }}">{{ $detailPrizePool }}</dd>
                                <dd class="mt-0.5 truncate text-[0.58rem] leading-none {{ $selectedRoom ? 'text-emerald-100/45' : 'text-slate-600' }}">{{ $detailFeeHint }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-2 shrink-0 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs leading-5 text-slate-500">
                            Beitritt, Reservierung und Spielstart folgen später.
                        </p>

                        <button type="button" class="cursor-not-allowed rounded-xl border border-slate-700 px-4 py-2 text-sm font-bold text-slate-500 opacity-70" disabled>
                            Beitreten
                        </button>
                    </div>
                </div>
            </aside>
        </section>

        <section class="grid min-h-0 flex-1 gap-3 overflow-hidden xl:grid-cols-12">
            <article class="flex min-h-0 flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70 p-4 shadow-2xl shadow-black/20 xl:col-span-9">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-300">
                            Raumliste
                        </p>
                        <h2 class="mt-1 text-xl font-black tracking-tight text-slate-100">
                            Verfügbare Spielräume
                        </h2>
                    </div>

                    <div class="rounded-2xl border border-slate-700 bg-slate-950/60 px-3 py-2 text-right text-xs text-slate-400">
                        <span class="font-black text-slate-100">{{ $gameRooms->count() }}</span>
                        Räume gefunden
                    </div>
                </div>

                <div class="mt-2 min-h-0 flex-1 overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/40">
                    @if ($gameRooms->isEmpty())
                        <div class="flex h-full min-h-48 items-center justify-center p-10 text-center">
                            <div>
                                <h3 class="text-lg font-black text-slate-100">Keine Räume gefunden</h3>
                                <p class="mt-2 text-sm text-slate-400">
                                    Passe die Filter an oder warte auf automatisch erzeugte Spielräume.
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="h-full min-h-0 overflow-auto">
                            <table class="min-w-full table-fixed divide-y divide-slate-800 text-left text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-950/95 text-[0.65rem] font-black uppercase tracking-wide text-slate-500 backdrop-blur">
                                    <tr>
                                        <th scope="col" class="w-[38%] px-4 py-2.5">Raum</th>
                                        <th scope="col" class="w-[13%] px-4 py-2.5 text-right">Buy-in</th>
                                        <th scope="col" class="w-[11%] px-4 py-2.5 text-right">Spieler</th>
                                        <th scope="col" class="w-[15%] px-4 py-2.5">Start</th>
                                        <th scope="col" class="w-[13%] px-4 py-2.5">Status</th>
                                        <th scope="col" class="w-[10%] px-4 py-2.5 text-right">Details</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-900/80">
                                    @foreach ($gameRooms as $room)
                                        @php
                                            $roomIsSelected = ($selectedRoom?->id ?? null) === $room->id;
                                            $roomStatusLabel = match ($room->status) {
                                                GameRoom::STATUS_OPEN => 'Offen',
                                                GameRoom::STATUS_FULL => 'Voll',
                                                GameRoom::STATUS_RUNNING => 'Läuft',
                                                GameRoom::STATUS_FINISHED => 'Beendet',
                                                default => $room->status,
                                            };

                                            $roomTargetQuery = request()->query();

                                            if ($roomIsSelected) {
                                                unset($roomTargetQuery['room']);
                                            } else {
                                                $roomTargetQuery['room'] = $room->public_code;
                                            }

                                            $roomTargetUrl = route('lobby', $roomTargetQuery);
                                        @endphp

                                        <tr
                                            class="{{ $roomIsSelected ? 'bg-amber-400/10 outline outline-1 -outline-offset-1 outline-amber-400/50' : 'bg-slate-950/20 hover:bg-slate-900/80' }} cursor-pointer transition focus-within:bg-slate-900/80"
                                            data-href="{{ $roomTargetUrl }}"
                                            role="link"
                                            tabindex="0"
                                            aria-label="{{ $roomIsSelected ? 'Auswahl für '.$room->name.' aufheben' : 'Details zu '.$room->name.' anzeigen' }}"
                                            onclick="window.location.href = this.dataset.href"
                                            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = this.dataset.href; }"
                                        >
                                            <td class="px-4 py-2">
                                                <span class="flex min-w-0 items-baseline gap-2">
                                                    <span class="truncate font-black text-slate-100">{{ $room->name }}</span>
                                                    <span class="shrink-0 font-mono text-[0.62rem] text-slate-600">{{ $room->public_code }}</span>
                                                </span>
                                            </td>

                                            <td class="px-4 py-2 text-right font-bold text-slate-200">
                                                {{ number_format($room->buy_in_units, 0, ',', '.') }} St$
                                            </td>

                                            <td class="px-4 py-2 text-right font-semibold text-slate-300">
                                                {{ $room->active_players_count }} / {{ $room->max_players }}
                                            </td>

                                            <td class="px-4 py-2 text-slate-300">
                                                {{ $room->isScheduled() ? 'Geplant' : 'Wenn voll' }}
                                            </td>

                                            <td class="px-4 py-2">
                                                <span class="inline-flex rounded-full border {{ $room->status === GameRoom::STATUS_OPEN ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300' : 'border-slate-700 bg-slate-950/70 text-slate-400' }} px-2 py-0.5 text-[0.65rem] font-black uppercase tracking-wide">
                                                    {{ $roomStatusLabel }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-2 text-right">
                                                <span class="{{ $roomIsSelected ? 'text-amber-300' : 'text-slate-500' }} font-black">
                                                    {{ $roomIsSelected ? '×' : '›' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </article>

            <aside class="flex min-h-0 flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70 p-4 shadow-2xl shadow-black/20 xl:col-span-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-300">
                        Globaler Chat
                    </p>
                    <h2 class="mt-1 text-xl font-black tracking-tight text-slate-100">
                        Lobby-Chat
                    </h2>
                </div>

                <div class="mt-4 min-h-0 flex-1 overflow-y-auto rounded-2xl border border-slate-800 bg-slate-950/40 p-4">
                    <div class="rounded-2xl border border-dashed border-slate-700 p-4 text-sm leading-6 text-slate-400">
                        Chat wird vorbereitet. Nachrichten, Systemhinweise und Raumereignisse erscheinen später hier.
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-500">
                    &gt; Nachricht...
                </div>
            </aside>
        </section>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>

