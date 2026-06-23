<script setup>
import { computed, reactive } from 'vue';

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
});

const roomCount = computed(() => {
    if (typeof props.meta?.count === 'number') {
        return props.meta.count;
    }

    return props.rooms.length;
});

const filterState = reactive({
    status: props.filters?.status || '',
    start_mode: props.filters?.start_mode || '',
    buy_in: props.filters?.buy_in || '',
    players: props.filters?.players || '',
});

const statusOptions = [
    { value: '', label: 'Alle' },
    { value: 'open', label: 'Offen' },
    { value: 'full', label: 'Voll' },
    { value: 'running', label: 'Läuft' },
    { value: 'finished', label: 'Beendet' },
];

const startModeOptions = [
    { value: '', label: 'Alle' },
    { value: 'when_full', label: 'Wenn voll' },
    { value: 'scheduled', label: 'Geplant' },
];

const buyInOptions = [
    { value: '', label: 'Alle' },
    { value: 'free', label: 'Kostenlos' },
    { value: 'micro', label: 'Mikro bis 500 St$' },
    { value: 'low', label: 'Low bis 2.000 St$' },
    { value: 'medium', label: 'Medium bis 10.000 St$' },
    { value: 'high', label: 'High ab 10.001 St$' },
];

const playerOptions = [
    { value: '', label: 'Alle' },
    { value: 'heads_up', label: '2 Spieler' },
    { value: 'small', label: '3-4 Spieler' },
    { value: 'medium', label: '5-6 Spieler' },
    { value: 'large', label: '7-11 Spieler' },
];

function lobbyUrl(overrides = {}) {
    const query = new URLSearchParams();
    const values = {
        status: filterState.status,
        start_mode: filterState.start_mode,
        buy_in: filterState.buy_in,
        players: filterState.players,
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

const applyFiltersUrl = computed(() => lobbyUrl());
const resetFiltersUrl = computed(() => '/lobby');

const selectedRoomDetails = computed(() => {
    if (!props.selectedRoomVisible || !props.selectedRoom) {
        return null;
    }

    return props.selectedRoom;
});

const roomList = computed(() => props.rooms || []);

const detailTitle = computed(() => selectedRoomDetails.value?.name || 'Kein Raum ausgewählt');
const detailCode = computed(() => selectedRoomDetails.value?.publicCode || '-');
const detailBuyIn = computed(() => selectedRoomDetails.value?.buyInDisplay || '-');
const detailPlayers = computed(() => selectedRoomDetails.value?.playersDisplay || '-');
const detailStart = computed(() => selectedRoomDetails.value?.startDisplay || '-');
const detailStatus = computed(() => selectedRoomDetails.value?.statusDisplay || '-');
const detailPrizePool = computed(() => selectedRoomDetails.value?.prizePoolDisplay || '-');
const detailFee = computed(() => selectedRoomDetails.value?.feeDisplay || 'Raum auswählen');

function roomIsSelected(room) {
    return Boolean(props.selectedRoomCode) && room?.publicCode === props.selectedRoomCode;
}

function roomUrl(room) {
    return lobbyUrl({
        room: roomIsSelected(room) ? '' : room?.publicCode,
    });
}

function rowAriaLabel(room) {
    if (roomIsSelected(room)) {
        return `Auswahl für ${room.name} aufheben`;
    }

    return `Details zu ${room.name} anzeigen`;
}
</script>

<template>
    <section class="flex min-h-0 flex-1 flex-col gap-3 overflow-hidden" aria-label="Lobby-Raumbrowser">
        <section class="grid h-[250px] shrink-0 items-stretch gap-3 overflow-hidden xl:grid-cols-12">
            <article class="flex h-full min-h-0 flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70 p-3 shadow-2xl shadow-black/20 xl:col-span-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-300">
                            Räume finden
                        </p>
                        <h2 class="mt-0.5 text-lg font-black tracking-tight text-slate-100">
                            Spielräume filtern
                        </h2>
                    </div>

                    <div class="shrink-0 rounded-2xl border border-slate-700 bg-slate-950/60 px-2.5 py-1.5 text-right text-[0.65rem] text-slate-400">
                        <span class="block text-base font-black leading-none text-slate-100">{{ roomCount }}</span>
                        Räume
                    </div>
                </div>

                <p class="mt-1 text-xs leading-4 text-slate-400">
                    Wähle passende Räume nach Status, Startmodus, Buy-in und Tischgröße.
                </p>

                <div class="mt-2 flex flex-1 flex-col justify-between gap-2">
                    <div class="grid gap-x-8 gap-y-2 sm:grid-cols-2">
                        <label class="flex items-center gap-3">
                            <span class="w-20 shrink-0 text-[0.58rem] font-black uppercase tracking-wide text-slate-500">Status</span>
                            <select
                                v-model="filterState.status"
                                class="h-8 w-24 shrink-0 rounded-lg border border-slate-700 bg-slate-950 px-2 text-xs text-slate-100"
                            >
                                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>

                        <label class="flex items-center gap-3">
                            <span class="w-20 shrink-0 text-[0.58rem] font-black uppercase tracking-wide text-slate-500">Startmodus</span>
                            <select
                                v-model="filterState.start_mode"
                                class="h-8 w-24 shrink-0 rounded-lg border border-slate-700 bg-slate-950 px-2 text-xs text-slate-100"
                            >
                                <option v-for="option in startModeOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>

                        <label class="flex items-center gap-3">
                            <span class="w-20 shrink-0 text-[0.58rem] font-black uppercase tracking-wide text-slate-500">Buy-in</span>
                            <select
                                v-model="filterState.buy_in"
                                class="h-8 w-24 shrink-0 rounded-lg border border-slate-700 bg-slate-950 px-2 text-xs text-slate-100"
                            >
                                <option v-for="option in buyInOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>

                        <label class="flex items-center gap-3">
                            <span class="w-20 shrink-0 text-[0.58rem] font-black uppercase tracking-wide text-slate-500">Tischgröße</span>
                            <select
                                v-model="filterState.players"
                                class="h-8 w-24 shrink-0 rounded-lg border border-slate-700 bg-slate-950 px-2 text-xs text-slate-100"
                            >
                                <option v-for="option in playerOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a
                            :href="applyFiltersUrl"
                            class="rounded-xl bg-amber-400 px-3 py-1.5 text-xs font-black text-slate-950 transition hover:bg-amber-300"
                        >
                            Filter anwenden
                        </a>

                        <a
                            :href="resetFiltersUrl"
                            class="rounded-xl border border-slate-700 px-3 py-1.5 text-xs font-bold text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
                        >
                            Zurücksetzen
                        </a>
                    </div>
                </div>
            </article>

            <aside class="flex h-full min-h-0 flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70 p-4 shadow-2xl shadow-black/20 xl:col-span-7">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-300">
                            Rauminformationen
                        </p>
                        <h2 class="mt-1 truncate text-xl font-black tracking-tight text-slate-100">
                            {{ detailTitle }}
                        </h2>

                        <p class="mt-1 truncate font-mono text-xs font-semibold text-slate-500">
                            {{ detailCode }}
                        </p>
                    </div>

                    <span
                        class="rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-wide"
                        :class="selectedRoomDetails
                            ? 'border-amber-400/40 bg-amber-400/10 text-amber-200'
                            : 'border-slate-700 bg-slate-950/70 text-slate-500'"
                    >
                        vorbereitet
                    </span>
                </div>

                <div
                    class="mt-3 flex min-h-0 flex-1 flex-col justify-between overflow-hidden rounded-2xl border p-3"
                    :class="selectedRoomDetails
                        ? 'border-amber-400/30 bg-slate-950/60'
                        : 'border-dashed border-slate-700 bg-slate-950/40'"
                >
                    <div v-if="!selectedRoomDetails" class="mb-3 text-sm leading-5 text-slate-400">
                        Klicke auf einen Raum in der Raumliste, um hier Details, Teilnahmebedingungen und Statusinformationen anzuzeigen.
                    </div>

                    <div class="min-h-0 flex-1">
                        <dl class="grid h-full grid-cols-5 gap-2 text-sm">
                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">Buy-in</dt>
                                <dd class="mt-1 truncate font-semibold leading-tight text-slate-300">{{ detailBuyIn }}</dd>
                            </div>

                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">Spieler</dt>
                                <dd class="mt-1 truncate font-semibold leading-tight text-slate-300">{{ detailPlayers }}</dd>
                            </div>

                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">Start</dt>
                                <dd class="mt-1 truncate font-semibold leading-tight text-slate-300">{{ detailStart }}</dd>
                            </div>

                            <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2">
                                <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">Status</dt>
                                <dd class="mt-1 truncate font-semibold leading-tight text-slate-300">{{ detailStatus }}</dd>
                            </div>

                            <div
                                class="rounded-xl border px-3 py-2"
                                :class="selectedRoomDetails
                                    ? 'border-emerald-400/20 bg-emerald-400/10'
                                    : 'border-slate-800 bg-slate-950/50'"
                            >
                                <dt
                                    class="text-[0.58rem] font-black uppercase leading-none tracking-wide"
                                    :class="selectedRoomDetails ? 'text-emerald-300' : 'text-slate-500'"
                                >
                                    Gewinnpool
                                </dt>
                                <dd
                                    class="mt-1 truncate font-black leading-tight"
                                    :class="selectedRoomDetails ? 'text-emerald-100' : 'text-slate-300'"
                                >
                                    {{ detailPrizePool }}
                                </dd>
                                <dd
                                    class="mt-0.5 truncate text-[0.58rem] leading-none"
                                    :class="selectedRoomDetails ? 'text-emerald-100/45' : 'text-slate-600'"
                                >
                                    {{ detailFee }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-2 shrink-0 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs leading-5 text-slate-500">
                            Beitritt, Reservierung und Spielstart folgen später.
                        </p>

                        <button
                            type="button"
                            class="cursor-not-allowed rounded-xl border border-slate-700 px-4 py-2 text-sm font-bold text-slate-500 opacity-70"
                            disabled
                        >
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
                        <h2 class="mt-0.5 text-lg font-black tracking-tight text-slate-100">
                            Verfügbare Spielräume
                        </h2>
                    </div>

                    <div class="rounded-2xl border border-slate-700 bg-slate-950/60 px-3 py-2 text-right text-xs text-slate-400">
                        <span class="font-black text-slate-100">{{ roomList.length }}</span>
                        Räume gefunden
                    </div>
                </div>

                <div class="mt-2 min-h-0 flex-1 overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/40">
                    <div v-if="!roomList.length" class="flex h-full min-h-48 items-center justify-center p-10 text-center">
                        <div>
                            <h3 class="text-lg font-black text-slate-100">Keine Räume gefunden</h3>
                            <p class="mt-2 text-sm text-slate-400">
                                Passe die Filter an oder warte auf automatisch erzeugte Spielräume.
                            </p>
                        </div>
                    </div>

                    <div v-else class="h-full min-h-0 overflow-auto">
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
                                <tr
                                    v-for="room in roomList"
                                    :key="room.publicCode"
                                    class="cursor-pointer transition focus-within:bg-slate-900/80"
                                    :class="roomIsSelected(room)
                                        ? 'bg-amber-400/10 outline outline-1 -outline-offset-1 outline-amber-400/50'
                                        : 'bg-slate-950/20 hover:bg-slate-900/80'"
                                >
                                    <td class="px-4 py-2">
                                        <a
                                            :href="roomUrl(room)"
                                            class="flex min-w-0 items-baseline gap-2"
                                            :aria-label="rowAriaLabel(room)"
                                        >
                                            <span class="truncate font-black text-slate-100">{{ room.name }}</span>
                                            <span class="shrink-0 font-mono text-[0.62rem] text-slate-600">{{ room.publicCode }}</span>
                                        </a>
                                    </td>

                                    <td class="px-4 py-2 text-right font-bold text-slate-200">
                                        <a :href="roomUrl(room)" class="block" :aria-label="rowAriaLabel(room)">
                                            {{ room.buyInDisplay }}
                                        </a>
                                    </td>

                                    <td class="px-4 py-2 text-right font-semibold text-slate-300">
                                        <a :href="roomUrl(room)" class="block" :aria-label="rowAriaLabel(room)">
                                            {{ room.playersDisplay }}
                                        </a>
                                    </td>

                                    <td class="px-4 py-2 text-slate-300">
                                        <a :href="roomUrl(room)" class="block" :aria-label="rowAriaLabel(room)">
                                            {{ room.startDisplay }}
                                        </a>
                                    </td>

                                    <td class="px-4 py-2">
                                        <a :href="roomUrl(room)" class="block" :aria-label="rowAriaLabel(room)">
                                            <span
                                                class="inline-flex rounded-full border px-2 py-0.5 text-[0.65rem] font-black uppercase tracking-wide"
                                                :class="room.statusTone === 'success'
                                                    ? 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300'
                                                    : 'border-slate-700 bg-slate-950/70 text-slate-400'"
                                            >
                                                {{ room.statusDisplay }}
                                            </span>
                                        </a>
                                    </td>

                                    <td class="px-4 py-2 text-right">
                                        <a
                                            :href="roomUrl(room)"
                                            class="font-black"
                                            :class="roomIsSelected(room) ? 'text-amber-300' : 'text-slate-500'"
                                            :aria-label="rowAriaLabel(room)"
                                        >
                                            {{ roomIsSelected(room) ? '×' : '›' }}
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>

            <aside class="flex min-h-0 flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70 p-4 shadow-2xl shadow-black/20 xl:col-span-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-300">
                        Globaler Chat
                    </p>
                    <h2 class="mt-0.5 text-lg font-black tracking-tight text-slate-100">
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
    </section>
</template>









