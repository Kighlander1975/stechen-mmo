<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';

const props = defineProps({
    rooms: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    selectedRoom: {
        type: Object,
        default: null,
    },
    selectedRoomCode: {
        type: String,
        default: null,
    },
    selectedRoomVisible: {
        type: Boolean,
        default: false,
    },
    meta: {
        type: Object,
        default: () => ({
            count: 0,
        }),
    },
    currentUser: {
        type: Object,
        default: () => ({
            activeParticipationCount: 0,
            waitingParticipationCount: 0,
            runningParticipationCount: 0,
            finishedParticipationCount: 0,
        }),
    },
});

const roomsState = ref([...(props.rooms || [])]);
const metaState = ref({ ...(props.meta || {}) });
const currentUserState = ref({ ...(props.currentUser || {}) });
const selectedRoomCodeState = ref(props.selectedRoomCode || props.selectedRoom?.publicCode || null);
const actionInProgressCode = ref(null);
const isSavingFilters = ref(false);
const toasts = ref([]);
const isPolling = ref(false);
const pollingError = ref('');
const lastUpdatedAt = ref(new Date());
const nowMs = ref(Date.now());
// Differenz Serverzeit minus Browserzeit, damit der Countdown zur Serveruhr passt.
const serverOffsetMs = ref(serverOffsetFrom(props.meta));
let pollingTimer = null;
let clockTimer = null;

const devMode = computed(() => metaState.value?.devMode === true);

const showOnlyTestRoomsFilter = computed(() => {
    return metaState.value?.canUseTestRoomFilter === true;
});

const roomCount = computed(() => {
    if (typeof metaState.value?.count === 'number') {
        return metaState.value.count;
    }

    return roomsState.value.length;
});

const filterState = reactive({
    status: props.filters?.status || '',
    start_mode: props.filters?.start_mode || '',
    buy_in: props.filters?.buy_in || '',
    players: props.filters?.players || '',
    only_test: Boolean(props.filters?.only_test),
});

const filterGroups = [
    {
        key: 'players',
        label: 'Tisch',
        options: [
            { value: '', label: 'Alle' },
            { value: 'heads_up', label: '2' },
            { value: 'small', label: '3–4' },
            { value: 'medium', label: '5–6' },
            { value: 'large', label: '7–11' },
        ],
    },
    {
        key: 'buy_in',
        label: 'Buy-in',
        options: [
            { value: '', label: 'Alle' },
            { value: 'free', label: 'Kostenlos' },
            { value: 'micro', label: 'bis 500' },
            { value: 'low', label: 'bis 2.000' },
            { value: 'medium', label: 'bis 10.000' },
            { value: 'high', label: 'über 10.000' },
        ],
    },
    {
        key: 'status',
        label: 'Status',
        options: [
            { value: '', label: 'Alle' },
            { value: 'open', label: 'Offen' },
            { value: 'full', label: 'Voll' },
            { value: 'running', label: 'Läuft' },
            { value: 'finished', label: 'Beendet' },
        ],
    },
    {
        key: 'start_mode',
        label: 'Start',
        options: [
            { value: '', label: 'Alle' },
            { value: 'when_full', label: 'Wenn voll' },
            { value: 'scheduled', label: 'Geplant' },
        ],
    },
];

const filtersAreDefault = computed(() => {
    return !filterState.status
        && !filterState.start_mode
        && !filterState.buy_in
        && !filterState.players
        && !filterState.only_test;
});

function serverOffsetFrom(meta) {
    const serverNow = Date.parse(meta?.serverNow || '');

    return Number.isNaN(serverNow) ? 0 : serverNow - Date.now();
}

function lobbyUrl(overrides = {}) {
    const query = new URLSearchParams();
    const values = {
        status: filterState.status,
        start_mode: filterState.start_mode,
        buy_in: filterState.buy_in,
        players: filterState.players,
        only_test: filterState.only_test ? '1' : '',
        ...overrides,
    };

    Object.entries(values).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            query.set(key, value);
        }
    });

    const queryString = query.toString();

    return queryString ? `/lobby?${queryString}` : '/lobby';
}

const roomList = computed(() => roomsState.value || []);

const allRoomsStartWhenFull = computed(() => {
    return roomList.value.length > 0
        && roomList.value.every((room) => room?.startMode === 'when_full');
});

const selectedRoomDetails = computed(() => {
    if (!selectedRoomCodeState.value) {
        return null;
    }

    return roomList.value.find((room) => room?.publicCode === selectedRoomCodeState.value) || null;
});

const availableUnits = computed(() => Number(currentUserState.value?.wallet?.availableUnits ?? 0));

// Zwei Bereiche mit gleicher Spaltenaufteilung: oben (fest) Spaltenköpfe und die eigenen Tische,
// darunter (scrollend) alle übrigen. Eigene Tische bleiben auch bei Filtern sichtbar, weil das
// Backend sie unabhängig vom Filter mitliefert.
const tableSections = computed(() => {
    return [
        {
            key: 'pinned',
            scrolls: false,
            rooms: roomList.value.filter((room) => participationOf(room).isParticipating),
        },
        {
            key: 'others',
            scrolls: true,
            rooms: roomList.value.filter((room) => !participationOf(room).isParticipating),
        },
    ];
});

const tableColumns = ['w-[36%]', 'w-[20%]', 'w-[13%]', 'w-[14%]', 'w-[17%]'];

const lastUpdatedDisplay = computed(() => {
    return lastUpdatedAt.value
        ? lastUpdatedAt.value.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
        : '-';
});

function participationOf(room) {
    return room?.currentUserParticipation || {
        isParticipating: false,
        status: null,
        seatNumber: null,
        reservedDisplay: '0 St$',
    };
}

function actionsOf(room) {
    return room?.actions || {
        joinUrl: null,
        leaveUrl: null,
        playUrl: null,
        showJoin: false,
        showLeave: false,
        showOpenGame: false,
    };
}

function canAfford(room) {
    return availableUnits.value >= Number(room?.buyInUnits ?? 0);
}

function isActionRunning(room) {
    return Boolean(room?.publicCode) && actionInProgressCode.value === room.publicCode;
}

function secondsUntilStart(room) {
    const startsAt = Date.parse(room?.startsAt || '');

    if (Number.isNaN(startsAt)) {
        return null;
    }

    return Math.max(0, Math.ceil((startsAt - (nowMs.value + serverOffsetMs.value)) / 1000));
}

function countdownDisplay(room) {
    const seconds = secondsUntilStart(room);

    if (seconds === null) {
        return '';
    }

    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
}

// Sitzplätze für Punkte und Mini-Tisch: eigener Platz, belegte und freie Plätze.
// Das Backend liefert nur die Anzahl belegter Plätze, daher werden fremde Plätze der Reihe nach gefüllt.
function seatsOf(room) {
    const maxPlayers = Math.max(0, Number(room?.maxPlayers ?? 0));
    const activePlayers = Math.min(maxPlayers, Number(room?.activePlayersCount ?? 0));
    const participation = participationOf(room);
    const ownIndex = participation.isParticipating && participation.seatNumber
        ? Math.min(maxPlayers, Number(participation.seatNumber)) - 1
        : -1;

    const seats = Array.from({ length: maxPlayers }, (_, index) => (index === ownIndex ? 'me' : 'free'));
    let othersToFill = activePlayers - (ownIndex >= 0 ? 1 : 0);

    for (let index = 0; index < maxPlayers && othersToFill > 0; index++) {
        if (seats[index] === 'free') {
            seats[index] = 'taken';
            othersToFill--;
        }
    }

    return seats;
}

// Positionen am ovalen Tisch: eigener Platz (sonst Platz 1) unten, die anderen gleichmäßig im Kreis.
function tableSeatsOf(room) {
    const seats = seatsOf(room);
    const count = seats.length;
    const bottomIndex = Math.max(0, seats.indexOf('me'));

    return seats.map((state, index) => {
        const angle = (270 + ((index - bottomIndex + count) % count) * (360 / count)) * (Math.PI / 180);

        return {
            state,
            left: 50 + 47 * Math.cos(angle),
            top: 50 - 42 * Math.sin(angle),
        };
    });
}

function roomRowState(room) {
    const participation = participationOf(room);
    const actions = actionsOf(room);

    if (actions.showOpenGame) {
        return 'open-game';
    }

    if (participation.isParticipating) {
        return 'seated';
    }

    if (actions.showJoin) {
        return canAfford(room) ? 'join' : 'too-expensive';
    }

    return 'status';
}

function selectedSubline(room) {
    const participation = participationOf(room);

    if (room.isStarting) {
        return participation.isParticipating ? 'Tisch voll · dein Platz ist verbindlich' : 'Tisch voll · startet gleich';
    }

    if (room.status === 'running') {
        return 'Das Spiel läuft';
    }

    if (room.status === 'finished') {
        return 'Das Spiel ist beendet';
    }

    const start = room.startMode === 'when_full' ? 'startet, sobald der Tisch voll ist' : 'geplanter Start';

    return `${room.maxPlayers} Plätze · ${start}`;
}

function selectedHint(room) {
    const participation = participationOf(room);
    const actions = actionsOf(room);

    if (actions.showLeave) {
        return `Du sitzt auf Platz ${participation.seatNumber || '-'}. Dein Buy-in von ${participation.reservedDisplay} ist reserviert und wird erst beim Spielstart abgebucht.`;
    }

    if (actions.showJoin && !canAfford(room)) {
        return 'Dein verfügbares Spielgeld reicht für diesen Tisch nicht aus.';
    }

    if (actions.showJoin) {
        return 'Beim Beitreten wird dein Buy-in reserviert und erst beim Spielstart abgebucht.';
    }

    return '';
}

function dismissToast(id) {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

function pushToast(type, message) {
    if (!message) {
        return;
    }

    const id = `${Date.now()}-${Math.random().toString(16).slice(2)}`;

    toasts.value = [...toasts.value, { id, type, message }];

    window.setTimeout(() => {
        dismissToast(id);
    }, 4500);
}

function dispatchWalletUpdated(payload) {
    const wallet = payload?.currentUser?.wallet;

    if (!wallet) {
        return;
    }

    window.dispatchEvent(new CustomEvent('stechen:wallet-updated', {
        detail: wallet,
    }));
}

function currentFilterParams() {
    return {
        status: filterState.status || undefined,
        start_mode: filterState.start_mode || undefined,
        buy_in: filterState.buy_in || undefined,
        players: filterState.players || undefined,
        only_test: filterState.only_test ? '1' : undefined,
        room: selectedRoomCodeState.value || undefined,
    };
}

function filterSnapshot(filters) {
    return {
        status: filters.status || null,
        start_mode: filters.start_mode || null,
        buy_in: filters.buy_in || null,
        players: filters.players || null,
        only_test: Boolean(filters.only_test),
    };
}

function syncFilterState(filters) {
    filterState.status = filters?.status || '';
    filterState.start_mode = filters?.start_mode || '';
    filterState.buy_in = filters?.buy_in || '';
    filterState.players = filters?.players || '';
    filterState.only_test = Boolean(filters?.only_test);
}

function applyLobbyPayload(payload) {
    if (!payload) {
        return;
    }

    roomsState.value = [...(payload.rooms || [])];
    metaState.value = { ...(payload.meta || {}) };
    currentUserState.value = { ...(payload.currentUser || {}) };
    serverOffsetMs.value = serverOffsetFrom(payload.meta);
    selectedRoomCodeState.value = payload.selectedRoomCode || payload.selectedRoom?.publicCode || selectedRoomCodeState.value;

    if (payload.filters && metaState.value?.canUseTestRoomFilter !== true && payload.filters.only_test !== true) {
        filterState.only_test = false;
    }

    dispatchWalletUpdated(payload);
}

async function saveFilterPreferences(filters) {
    const url = metaState.value?.preferencesUrl;

    if (!url || isSavingFilters.value) {
        return;
    }

    const previousFilters = filterSnapshot(filterState);
    isSavingFilters.value = true;
    syncFilterState(filters);

    try {
        const response = await window.axios.put(url, {
            ...filterSnapshot(filters),
            room: null,
        });

        syncFilterState(response.data?.filters || response.data?.lobby?.filters);
        selectedRoomCodeState.value = null;
        applyLobbyPayload(response.data?.lobby);
        window.history.replaceState({}, '', lobbyUrl());
        lastUpdatedAt.value = new Date();
        pollingError.value = '';
    } catch (error) {
        syncFilterState(previousFilters);
        pushToast('error', error?.response?.data?.message || 'Lobbyfilter konnten nicht gespeichert werden.');
    } finally {
        isSavingFilters.value = false;
    }
}

function selectFilter(key, value) {
    if (filterState[key] === value) {
        return;
    }

    saveFilterPreferences({ ...filterSnapshot(filterState), [key]: value || null });
}

function toggleOnlyTestRooms() {
    saveFilterPreferences({ ...filterSnapshot(filterState), only_test: !filterState.only_test });
}

function resetFilters() {
    saveFilterPreferences({
        status: null,
        start_mode: null,
        buy_in: null,
        players: null,
        only_test: false,
    });
}

async function refreshLobbyRooms({ silent = true } = {}) {
    if (isPolling.value || actionInProgressCode.value || isSavingFilters.value) {
        return;
    }

    isPolling.value = true;

    try {
        const response = await window.axios.get('/lobby/rooms', {
            params: currentFilterParams(),
        });

        applyLobbyPayload(response.data);
        lastUpdatedAt.value = new Date();
        pollingError.value = '';
    } catch (error) {
        pollingError.value = 'Aktualisierung kurzzeitig nicht möglich.';

        if (!silent) {
            pushToast('error', pollingError.value);
        }
    } finally {
        isPolling.value = false;
    }
}

async function postRoomAction(room, url, fallbackErrorMessage) {
    if (!url || !room?.publicCode || actionInProgressCode.value) {
        return;
    }

    actionInProgressCode.value = room.publicCode;

    try {
        const response = await window.axios.post(url, {}, {
            params: currentFilterParams(),
        });

        applyLobbyPayload(response.data?.lobby);
        pushToast('success', response.data?.message || 'Aktion wurde ausgeführt.');
    } catch (error) {
        applyLobbyPayload(error?.response?.data?.lobby);
        pushToast('error', error?.response?.data?.message || fallbackErrorMessage);
    } finally {
        actionInProgressCode.value = null;
    }
}

function joinRoom(room) {
    selectedRoomCodeState.value = room.publicCode;
    postRoomAction(room, actionsOf(room).joinUrl, 'Du konntest dem Tisch nicht beitreten.');
}

function leaveRoom(room) {
    postRoomAction(room, actionsOf(room).leaveUrl, 'Dein Platz konnte nicht freigegeben werden.');
}

function openGame(room) {
    const playUrl = actionsOf(room).playUrl;

    if (!playUrl) {
        return;
    }

    window.open(playUrl, `stechen-game-${room.publicCode || 'unknown'}`);
}

function roomIsSelected(room) {
    return Boolean(selectedRoomCodeState.value) && room?.publicCode === selectedRoomCodeState.value;
}

function toggleRoomSelection(room) {
    if (!room?.publicCode) {
        return;
    }

    selectedRoomCodeState.value = roomIsSelected(room) ? null : room.publicCode;
}

function roomUrl(room) {
    return lobbyUrl({
        room: roomIsSelected(room) ? '' : room?.publicCode,
    });
}

function rowAriaLabel(room) {
    return roomIsSelected(room)
        ? `Auswahl für ${room.name} aufheben`
        : `Details zu ${room.name} anzeigen`;
}

onMounted(() => {
    pollingTimer = window.setInterval(() => {
        refreshLobbyRooms();
    }, 3000);

    clockTimer = window.setInterval(() => {
        nowMs.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    window.clearInterval(pollingTimer);
    window.clearInterval(clockTimer);
    pollingTimer = null;
    clockTimer = null;
});
</script>

<template>
    <section class="relative flex min-h-0 flex-1 flex-col gap-4 tabular-nums" aria-label="Spiellobby">
        <div class="pointer-events-none fixed right-6 top-6 z-50 flex w-full max-w-sm flex-col gap-3">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto rounded-2xl border px-4 py-3 shadow-2xl shadow-black/30 backdrop-blur"
                :class="toast.type === 'error'
                    ? 'border-red-400/40 bg-red-950/90 text-red-100'
                    : 'border-emerald-400/40 bg-emerald-950/90 text-emerald-100'"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-bold leading-5">
                        {{ toast.message }}
                    </p>

                    <button
                        type="button"
                        class="shrink-0 rounded-lg px-2 py-0.5 text-lg leading-none opacity-70 transition hover:bg-white/10 hover:opacity-100"
                        aria-label="Meldung schließen"
                        @click="dismissToast(toast.id)"
                    >
                        ×
                    </button>
                </div>
            </div>
        </div>

        <div class="grid min-h-0 flex-1 gap-4 lg:grid-cols-[minmax(0,1fr)_24rem]">
            <!-- Tischliste -->
            <article class="flex min-h-0 flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80 shadow-2xl shadow-black/20">
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-b border-slate-800 px-4 py-3">
                    <div
                        v-for="group in filterGroups"
                        :key="group.key"
                        class="flex flex-wrap items-center gap-1.5"
                        role="group"
                        :aria-label="`Filter ${group.label}`"
                    >
                        <span class="mr-0.5 text-xs font-bold uppercase tracking-wider text-slate-400">{{ group.label }}</span>
                        <button
                            v-for="option in group.options"
                            :key="option.value"
                            type="button"
                            class="rounded-full border px-2.5 py-0.5 text-[0.8125rem] transition disabled:cursor-wait"
                            :class="filterState[group.key] === option.value
                                ? 'border-amber-400 bg-amber-400 font-bold text-slate-950'
                                : 'border-slate-700 bg-slate-800/70 text-slate-300 hover:border-slate-500 hover:text-slate-100'"
                            :aria-pressed="filterState[group.key] === option.value"
                            :disabled="isSavingFilters"
                            @click="selectFilter(group.key, option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <button
                        v-if="showOnlyTestRoomsFilter"
                        type="button"
                        class="rounded-full border px-2.5 py-0.5 text-[0.8125rem] transition disabled:cursor-wait"
                        :class="filterState.only_test
                            ? 'border-amber-400 bg-amber-400 font-bold text-slate-950'
                            : 'border-dashed border-amber-400/50 text-amber-300 hover:border-amber-300'"
                        :aria-pressed="filterState.only_test"
                        :disabled="isSavingFilters"
                        @click="toggleOnlyTestRooms"
                    >
                        Nur Testräume
                    </button>

                    <div class="ml-auto flex items-center gap-3 text-sm text-slate-400">
                        <button
                            v-if="!filtersAreDefault"
                            type="button"
                            class="underline-offset-4 transition hover:text-amber-300 hover:underline disabled:cursor-wait"
                            :disabled="isSavingFilters"
                            @click="resetFilters"
                        >
                            Zurücksetzen
                        </button>
                        <span><b class="text-slate-100">{{ roomCount }}</b> Tische</span>
                    </div>
                </div>

                <div v-if="!roomList.length" class="flex min-h-48 flex-1 items-center justify-center p-10 text-center">
                    <div>
                        <h3 class="text-lg font-bold text-slate-100">Keine Tische gefunden</h3>
                        <p class="mt-2 text-slate-400">
                            Passe die Filter an oder warte, bis neue Tische bereitgestellt werden.
                        </p>
                    </div>
                </div>

                <!-- Beide Bereiche reservieren Platz für den Scrollbalken, damit die Spalten exakt übereinanderstehen. -->
                <div
                    v-for="section in (roomList.length ? tableSections : [])"
                    :key="section.key"
                    :class="section.scrolls
                        ? 'max-h-[60vh] min-h-0 flex-1 overflow-y-auto [scrollbar-gutter:stable] lg:max-h-none'
                        : 'shrink-0 overflow-y-hidden bg-slate-800/40 [scrollbar-gutter:stable]'
                            + (section.rooms.length ? ' border-b-2 border-amber-400/45' : '')"
                    :aria-label="section.scrolls ? 'Verfügbare Tische' : 'Deine Tische'"
                >
                    <table class="w-full table-fixed text-left">
                        <colgroup>
                            <col v-for="width in tableColumns" :key="width" :class="width">
                        </colgroup>

                        <thead
                            v-if="!section.scrolls"
                            class="bg-slate-900 text-xs font-bold uppercase tracking-wider text-slate-400 shadow-[inset_0_-1px_0_rgb(30_41_59)]"
                        >
                            <tr>
                                <th scope="col" class="h-11 px-4">Tisch</th>
                                <th scope="col" class="h-11 px-4">Plätze</th>
                                <th scope="col" class="h-11 px-4 text-right">Buy-in</th>
                                <th scope="col" class="h-11 px-4 text-right">Gewinnpool</th>
                                <th scope="col" class="h-11 px-4"><span class="sr-only">Aktion</span></th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="room in section.rooms"
                                :key="room.publicCode"
                                class="cursor-pointer border-t border-slate-800 transition first:border-t-0"
                                :class="roomIsSelected(room)
                                    ? 'bg-amber-400/10'
                                    : 'hover:bg-slate-800/50'"
                                @click="toggleRoomSelection(room)"
                            >
                                <td class="h-14 px-4" :class="{ 'shadow-[inset_4px_0_0_rgb(251_191_36)]': roomIsSelected(room) }">
                                    <a
                                        :href="roomUrl(room)"
                                        class="flex min-w-0 flex-nowrap items-center gap-2 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"
                                        :aria-label="rowAriaLabel(room)"
                                        @click.prevent.stop="toggleRoomSelection(room)"
                                    >
                                        <span class="min-w-0 truncate text-base font-bold text-slate-100">{{ room.name }}</span>
                                        <span
                                            v-if="participationOf(room).isParticipating"
                                            class="shrink-0 rounded-full bg-emerald-400/15 px-2 py-0.5 text-xs font-bold text-emerald-300"
                                        >Dabei</span>
                                        <span
                                            v-if="room.isStarting"
                                            class="shrink-0 whitespace-nowrap rounded-full bg-amber-400/15 px-2 py-0.5 text-xs font-bold text-amber-300"
                                        >Startet {{ countdownDisplay(room) }}</span>
                                        <span
                                            v-if="room.isTest"
                                            class="shrink-0 rounded-full border border-amber-400/30 px-2 py-0.5 text-xs font-bold text-amber-300"
                                        >Test</span>
                                        <span
                                            v-if="devMode"
                                            class="min-w-0 truncate font-mono text-xs text-slate-500"
                                            title="Nur im Dev-Modus sichtbar"
                                        >{{ room.publicCode }}</span>
                                    </a>
                                </td>

                                <td class="h-14 whitespace-nowrap px-4">
                                    <span class="inline-flex flex-nowrap items-center gap-1 align-middle" aria-hidden="true">
                                        <i
                                            v-for="(seat, index) in seatsOf(room)"
                                            :key="index"
                                            class="block rounded-full border-2"
                                            :class="[
                                                room.maxPlayers > 6 ? 'h-2.5 w-2.5' : 'h-3 w-3',
                                                {
                                                    'border-amber-400 bg-amber-400': seat === 'me',
                                                    'border-slate-400 bg-slate-400': seat === 'taken',
                                                    'border-slate-600': seat === 'free',
                                                },
                                            ]"
                                        ></i>
                                    </span>
                                    <span class="ml-2 align-middle text-slate-400">{{ room.playersDisplay }}</span>
                                </td>

                                <td class="h-14 px-4 text-right text-slate-100">{{ room.buyInDisplay }}</td>
                                <td class="h-14 px-4 text-right text-slate-100">{{ room.prizePoolDisplay }}</td>

                                <td class="h-14 px-4 text-right">
                                    <button
                                        v-if="roomRowState(room) === 'join'"
                                        type="button"
                                        class="rounded-lg border border-slate-600 bg-slate-800 px-3 py-1 text-sm font-bold text-slate-100 transition hover:border-amber-400 hover:text-amber-300 disabled:cursor-wait disabled:opacity-60"
                                        :disabled="isActionRunning(room)"
                                        @click.stop="joinRoom(room)"
                                    >
                                        {{ isActionRunning(room) ? 'Prüfe …' : 'Beitreten' }}
                                    </button>
                                    <span
                                        v-else-if="roomRowState(room) === 'too-expensive'"
                                        class="inline-block rounded-lg border border-dashed border-slate-700 px-3 py-1 text-sm text-slate-500"
                                        title="Dein verfügbares Spielgeld reicht nicht aus"
                                    >Zu teuer</span>
                                    <button
                                        v-else-if="roomRowState(room) === 'open-game'"
                                        type="button"
                                        class="rounded-lg bg-amber-400 px-3 py-1 text-sm font-bold text-slate-950 transition hover:bg-amber-300"
                                        @click.stop="openGame(room)"
                                    >
                                        Zum Tisch
                                    </button>
                                    <span v-else-if="roomRowState(room) === 'seated'" class="text-sm text-slate-400">
                                        Platz {{ participationOf(room).seatNumber || '-' }}
                                    </span>
                                    <span v-else class="text-sm text-slate-400">{{ room.statusDisplay }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-800 px-4 py-2.5 text-sm text-slate-400">
                    <span v-if="allRoomsStartWhenFull">Alle Tische starten, sobald alle Plätze besetzt sind.</span>
                    <span v-else></span>
                    <span :class="pollingError ? 'text-red-300' : ''">
                        {{ pollingError || `Aktualisiert ${lastUpdatedDisplay}` }}
                    </span>
                </div>
            </article>

            <!-- Rechte Spalte: ausgewählter Tisch und (später) Chat -->
            <aside class="flex min-h-0 flex-col gap-4">
                <section class="shrink-0 rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-2xl shadow-black/20" aria-live="polite">
                    <template v-if="selectedRoomDetails">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ausgewählter Tisch</p>
                                <h2 class="truncate text-2xl font-bold text-slate-100">{{ selectedRoomDetails.name }}</h2>
                                <p class="text-sm text-slate-400">{{ selectedSubline(selectedRoomDetails) }}</p>
                                <p
                                    v-if="devMode"
                                    class="mt-1 inline-block rounded border border-dashed border-slate-600 px-1.5 font-mono text-xs text-slate-500"
                                    title="Nur im Dev-Modus sichtbar: Raumcode als Prüfsumme"
                                >{{ selectedRoomDetails.publicCode }}</p>
                            </div>

                            <div v-if="selectedRoomDetails.isStarting" class="shrink-0 text-right text-amber-300">
                                <p class="text-3xl font-bold leading-none">{{ countdownDisplay(selectedRoomDetails) }}</p>
                                <p class="text-xs font-bold uppercase tracking-wider">bis Start</p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-[9rem_minmax(0,1fr)] items-center gap-4">
                            <div class="space-y-2">
                                <div class="rounded-xl bg-slate-800/70 px-3 py-2">
                                    <p class="text-sm text-slate-400">Buy-in</p>
                                    <p class="text-lg font-bold text-slate-100">{{ selectedRoomDetails.buyInDisplay }}</p>
                                </div>
                                <div class="rounded-xl border border-emerald-400/35 bg-slate-800/70 px-3 py-2">
                                    <p class="text-sm text-slate-400">Gewinnpool</p>
                                    <p class="text-lg font-bold text-emerald-300">{{ selectedRoomDetails.prizePoolDisplay }}</p>
                                </div>
                            </div>

                            <!-- Mini-Pokertisch: Grundlage für das spätere Spielfeld -->
                            <div class="relative mx-2 my-4 h-24">
                                <div class="absolute inset-0 rounded-[3rem] border-4 border-[#3a2a17] bg-[radial-gradient(ellipse_at_center,#1f5b44_0%,#163f31_70%)]"></div>
                                <span
                                    v-for="(seat, index) in tableSeatsOf(selectedRoomDetails)"
                                    :key="index"
                                    class="absolute flex h-5 w-5 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2"
                                    :class="{
                                        'border-amber-400 bg-amber-400': seat.state === 'me',
                                        'border-slate-300 bg-slate-300': seat.state === 'taken',
                                        'border-dashed border-slate-300 bg-slate-950': seat.state === 'free',
                                    }"
                                    :style="{ left: `${seat.left}%`, top: `${seat.top}%` }"
                                    :title="seat.state === 'me' ? 'Du' : seat.state === 'taken' ? 'Belegt' : 'Frei'"
                                ></span>
                                <span
                                    v-if="participationOf(selectedRoomDetails).isParticipating"
                                    class="absolute left-1/2 top-full mt-2 -translate-x-1/2 text-xs font-bold text-amber-300"
                                >Du</span>
                            </div>
                        </div>

                        <p class="mt-3 text-sm text-slate-400">{{ selectedRoomDetails.feeDisplay }}.</p>
                        <p v-if="selectedHint(selectedRoomDetails)" class="mt-1 text-sm text-slate-300">
                            {{ selectedHint(selectedRoomDetails) }}
                        </p>

                        <div class="mt-4 space-y-2">
                            <button
                                v-if="actionsOf(selectedRoomDetails).showOpenGame"
                                type="button"
                                class="w-full rounded-xl bg-amber-400 px-4 py-2.5 font-bold text-slate-950 transition hover:bg-amber-300"
                                @click="openGame(selectedRoomDetails)"
                            >
                                Zum Spieltisch
                            </button>

                            <button
                                v-if="actionsOf(selectedRoomDetails).showJoin"
                                type="button"
                                class="w-full rounded-xl bg-amber-400 px-4 py-2.5 font-bold text-slate-950 transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:bg-slate-700 disabled:text-slate-400"
                                :disabled="isActionRunning(selectedRoomDetails) || !canAfford(selectedRoomDetails)"
                                @click="joinRoom(selectedRoomDetails)"
                            >
                                {{ isActionRunning(selectedRoomDetails) ? 'Prüfe …' : canAfford(selectedRoomDetails) ? 'Beitreten' : 'Guthaben reicht nicht' }}
                            </button>

                            <button
                                v-if="actionsOf(selectedRoomDetails).showLeave"
                                type="button"
                                class="w-full rounded-xl border border-red-400/50 px-4 py-2.5 font-bold text-red-300 transition hover:border-red-300 hover:text-red-200 disabled:cursor-wait disabled:opacity-60"
                                :disabled="isActionRunning(selectedRoomDetails)"
                                @click="leaveRoom(selectedRoomDetails)"
                            >
                                {{ isActionRunning(selectedRoomDetails) ? 'Gebe frei …' : 'Platz freigeben' }}
                            </button>
                        </div>
                    </template>

                    <template v-else>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ausgewählter Tisch</p>
                        <p class="mt-2 text-slate-300">Wähle einen Tisch aus der Liste, um Details zu sehen und beizutreten.</p>
                    </template>
                </section>

                <!-- Lobby-Chat kommt mit der Node.js-Echtzeit-Engine; bis dahin nur als Dev-Platzhalter. -->
                <section
                    v-if="devMode"
                    class="flex min-h-[10rem] flex-1 flex-col rounded-2xl border border-dashed border-slate-700 bg-slate-900/50 p-5"
                    title="Nur im Dev-Modus sichtbar"
                >
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Lobby-Chat</p>
                    <p class="mt-2 text-sm text-slate-500">
                        Platzhalter: Der Chat kommt mit der Echtzeit-Engine (Node.js).
                    </p>
                </section>
            </aside>
        </div>
    </section>
</template>
