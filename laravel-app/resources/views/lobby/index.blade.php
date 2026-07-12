@php
    use App\Models\GameRoom;
@endphp

<x-app-layout
    header-eyebrow="SPIELLOBBY"
    :header-title="'Lobby · Angemeldet als: '.Auth::user()->name"
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
                        class="flex min-h-0 flex-1 flex-col"
                        data-vue-component="lobby-room-browser"
                        data-props='{{ $lobbyRoomBrowserJson }}'
                    ></div>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>

