<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    publicCode: {
        type: String,
        required: true,
    },
    initialState: {
        type: Object,
        required: true,
    },
    stateUrl: {
        type: String,
        required: true,
    },
    finishUrl: {
        type: String,
        required: true,
    },
});

const state = ref(props.initialState);
const isLoading = ref(false);
const isFinishing = ref(false);
const errorMessage = ref('');
const lastUpdatedAt = ref(new Date());
const localNow = ref(new Date());
const pollIntervalId = ref(null);
const clockIntervalId = ref(null);

const room = computed(() => state.value?.room || {});
const players = computed(() => state.value?.players || []);
const field = computed(() => state.value?.field || {});
const finish = computed(() => state.value?.finish || {
    finishedCount: 0,
    requiredCount: 0,
    currentUserFinished: false,
    canFinish: false,
});

const statusToneClasses = computed(() => {
    if (room.value.isFinished) {
        return 'border-emerald-400/40 bg-emerald-400/10 text-emerald-200';
    }

    if (room.value.isRunning) {
        return 'border-sky-400/40 bg-sky-400/10 text-sky-200';
    }

    return 'border-amber-400/40 bg-amber-400/10 text-amber-200';
});

const localStartsInSeconds = computed(() => {
    if (!room.value.startsAt) {
        return null;
    }

    const startsAt = new Date(room.value.startsAt);
    const diffMs = startsAt.getTime() - localNow.value.getTime();

    return Math.max(0, Math.ceil(diffMs / 1000));
});

const countdownLabel = computed(() => {
    if (!room.value.isStarting) {
        return null;
    }

    const seconds = localStartsInSeconds.value ?? room.value.startsInSeconds;

    if (seconds === null || seconds === undefined) {
        return 'Start wird vorbereitet';
    }

    if (seconds <= 0) {
        return 'Start wird finalisiert';
    }

    return `${seconds} Sekunden`;
});

const finishButtonLabel = computed(() => {
    const finishedCount = finish.value.finishedCount ?? 0;
    const requiredCount = finish.value.requiredCount ?? players.value.length;

    if (room.value.isFinished) {
        return `Spiel beendet (${finishedCount}/${requiredCount})`;
    }

    if (finish.value.currentUserFinished) {
        return `Warten auf andere Spieler (${finishedCount}/${requiredCount})`;
    }

    return `Spiel beenden (${finishedCount}/${requiredCount})`;
});

const finishProgressPercent = computed(() => {
    const requiredCount = finish.value.requiredCount || players.value.length || 0;

    if (requiredCount <= 0) {
        return 0;
    }

    return Math.min(100, Math.round(((finish.value.finishedCount || 0) / requiredCount) * 100));
});

const ownSeatNumber = computed(() => field.value.ownSeatNumber ?? null);

async function refreshState() {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await window.axios.get(props.stateUrl);
        state.value = response.data;
        lastUpdatedAt.value = new Date();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Spielzustand konnte nicht geladen werden.';
    } finally {
        isLoading.value = false;
    }
}

async function finishGame() {
    if (!finish.value.canFinish || isFinishing.value) {
        return;
    }

    isFinishing.value = true;
    errorMessage.value = '';

    try {
        const response = await window.axios.post(props.finishUrl);
        state.value = response.data;
        lastUpdatedAt.value = new Date();
    } catch (error) {
        errorMessage.value = error?.response?.data?.message || 'Spiel konnte nicht beendet werden.';
    } finally {
        isFinishing.value = false;
    }
}

function openLobby() {
    window.location.href = '/lobby';
}

onMounted(() => {
    clockIntervalId.value = window.setInterval(() => {
        localNow.value = new Date();
    }, 250);

    pollIntervalId.value = window.setInterval(() => {
        refreshState();
    }, 2000);
});

onBeforeUnmount(() => {
    if (clockIntervalId.value) {
        window.clearInterval(clockIntervalId.value);
    }

    if (pollIntervalId.value) {
        window.clearInterval(pollIntervalId.value);
    }
});
</script>

<template>
    <main class="min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,rgba(251,191,36,0.10),transparent_34%),linear-gradient(180deg,#020617,#0f172a_48%,#020617)] text-slate-100">
        <div class="flex min-h-screen flex-col">
            <header class="shrink-0 border-b border-slate-800 bg-slate-950/80 px-6 py-4 backdrop-blur">
                <div class="mx-auto flex max-w-[1800px] flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.25em] text-amber-300">
                            Stechen-MMO Spieltisch
                        </p>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-black tracking-tight text-slate-50">
                                {{ room.name || publicCode }}
                            </h1>
                            <span class="rounded-full border px-3 py-1 text-xs font-black uppercase tracking-wide" :class="statusToneClasses">
                                {{ room.statusLabel || room.status }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-400">
                            Raumcode:
                            <span class="font-mono font-bold text-slate-200">{{ room.publicCode || publicCode }}</span>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div v-if="room.isStarting" class="rounded-2xl border border-amber-400/30 bg-amber-400/10 px-5 py-3 text-right">
                            <p class="text-xs font-black uppercase tracking-wide text-amber-300">
                                Spiel startet in
                            </p>
                            <p class="mt-1 text-2xl font-black text-amber-100">
                                {{ countdownLabel }}
                            </p>
                        </div>

                        <div v-else-if="room.isRunning" class="rounded-2xl border border-sky-400/30 bg-sky-400/10 px-5 py-3 text-right">
                            <p class="text-xs font-black uppercase tracking-wide text-sky-300">
                                Testspiel läuft
                            </p>
                            <p class="mt-1 text-sm font-bold text-sky-100">
                                Alle Spieler müssen beenden.
                            </p>
                        </div>

                        <div v-else-if="room.isFinished" class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-5 py-3 text-right">
                            <p class="text-xs font-black uppercase tracking-wide text-emerald-300">
                                Spiel beendet
                            </p>
                            <p class="mt-1 text-sm font-bold text-emerald-100">
                                Buy-in wurde zurückgebucht.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-2xl border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm font-black uppercase tracking-wide text-slate-300 transition hover:border-slate-500 hover:text-white"
                            @click="openLobby"
                        >
                            Zur Lobby
                        </button>
                    </div>
                </div>
            </header>

            <section class="mx-auto grid min-h-0 w-full max-w-[1800px] flex-1 gap-5 p-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="grid min-h-0 gap-5 xl:grid-rows-[minmax(0,1fr)_auto]">
                    <article class="relative min-h-[34rem] overflow-hidden rounded-[2rem] border border-slate-800 bg-slate-950/45 shadow-2xl shadow-black/40">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(251,191,36,0.12),transparent_42%)]"></div>

                        <div
                            v-if="field.showActiveSeatMarker && field.activeSeatNumber"
                            class="pointer-events-none absolute inset-0 opacity-40"
                        ></div>

                        <div class="absolute left-1/2 top-1/2 flex h-60 w-60 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-[2rem] border border-amber-400/20 bg-slate-900/90 text-center shadow-2xl shadow-black/40">
                            <div>
                                <p class="text-xs font-black uppercase tracking-wide text-amber-300">
                                    Tischmitte
                                </p>
                                <p class="mt-2 text-sm leading-6 text-slate-400">
                                    Stich, Ablage und Rundenzustand folgen mit der Spiellogik.
                                </p>
                                <p v-if="!field.showActiveSeatMarker" class="mt-3 rounded-full border border-slate-700 bg-slate-950/70 px-3 py-1 text-xs font-bold text-slate-400">
                                    Kein aktiver Spieler markiert
                                </p>
                            </div>
                        </div>

                        <div
                            v-for="player in players"
                            :key="player.id"
                            class="absolute flex h-20 w-36 -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center rounded-2xl border text-center shadow-xl shadow-black/30"
                            :class="player.isCurrentUser ? 'border-amber-400/60 bg-amber-400/15' : 'border-slate-700 bg-slate-950/90'"
                            :style="seatStyle(player.seatNumber)"
                        >
                            <p class="text-[0.65rem] font-black uppercase leading-none tracking-wide" :class="player.isCurrentUser ? 'text-amber-300' : 'text-slate-500'">
                                {{ player.isCurrentUser ? 'Du' : `Spieler ${player.seatNumber}` }}
                            </p>
                            <p class="mt-1 max-w-[8rem] truncate text-sm font-black leading-none text-slate-100">
                                {{ player.displayName }}
                            </p>
                            <p class="mt-2 rounded-full border px-2 py-0.5 text-[0.65rem] font-bold" :class="player.hasFinished ? 'border-emerald-400/30 text-emerald-300' : 'border-slate-700 text-slate-400'">
                                {{ player.hasFinished ? 'Beendet' : player.statusLabel }}
                            </p>
                        </div>

                        <div class="absolute bottom-4 left-1/2 w-[min(36rem,calc(100%-2rem))] -translate-x-1/2 rounded-[1.25rem] border border-amber-400/40 bg-amber-400/10 px-5 py-3 text-center shadow-xl shadow-black/20">
                            <p class="text-[0.65rem] font-black uppercase tracking-wide text-amber-300">
                                Eigener Platz
                            </p>
                            <p class="mt-1 text-sm font-bold text-slate-100">
                                Du sitzt auf Platz {{ ownSeatNumber || '-' }}. Die finale Kartenlogik folgt später.
                            </p>
                        </div>
                    </article>

                    <aside class="rounded-[1.5rem] border border-slate-800 bg-slate-900/70 p-5 shadow-2xl shadow-black/20">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="text-xs font-black uppercase tracking-wide text-amber-300">
                                    Testaktionen
                                </p>
                                <h2 class="mt-1 text-xl font-black tracking-tight text-slate-100">
                                    Spielabschluss
                                </h2>
                                <p class="mt-2 text-sm leading-6 text-slate-400">
                                    Für den MVP-Test müssen alle aktiven Spieler auf „Spiel beenden“ klicken.
                                </p>
                            </div>

                            <div class="w-full max-w-md">
                                <div class="mb-3 h-2 overflow-hidden rounded-full bg-slate-800">
                                    <div
                                        class="h-full rounded-full bg-emerald-400 transition-all"
                                        :style="{ width: `${finishProgressPercent}%` }"
                                    ></div>
                                </div>

                                <button
                                    type="button"
                                    class="w-full rounded-2xl px-5 py-3 text-sm font-black uppercase tracking-wide transition"
                                    :class="finish.canFinish && !isFinishing
                                        ? 'bg-amber-400 text-slate-950 shadow-lg shadow-amber-950/30 hover:bg-amber-300'
                                        : 'cursor-not-allowed border border-slate-700 bg-slate-950/60 text-slate-500'"
                                    :disabled="!finish.canFinish || isFinishing"
                                    @click="finishGame"
                                >
                                    {{ isFinishing ? 'Wird gespeichert...' : finishButtonLabel }}
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>

                <aside class="flex min-h-0 flex-col rounded-[1.5rem] border border-slate-800 bg-slate-900/70 p-5 shadow-2xl shadow-black/20">
                    <div class="shrink-0">
                        <p class="text-xs font-black uppercase tracking-wide text-amber-300">
                            Tischstatus
                        </p>
                        <h2 class="mt-1 text-xl font-black tracking-tight text-slate-100">
                            Spieler
                        </h2>
                    </div>

                    <div class="mt-4 min-h-0 flex-1 overflow-y-auto rounded-2xl border border-slate-800 bg-slate-950/40 p-3">
                        <div class="space-y-3">
                            <div
                                v-for="player in players"
                                :key="`list-${player.id}`"
                                class="rounded-2xl border px-3 py-3"
                                :class="player.isCurrentUser ? 'border-amber-400/40 bg-amber-400/10' : 'border-slate-800 bg-slate-950/50'"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-slate-100">
                                            Platz {{ player.seatNumber }} · {{ player.displayName }}
                                        </p>
                                        <p class="mt-1 text-xs font-bold text-slate-500">
                                            {{ player.isCurrentUser ? 'Du' : 'Mitspieler' }}
                                        </p>
                                    </div>

                                    <span class="shrink-0 rounded-full border px-2 py-1 text-[0.65rem] font-black uppercase tracking-wide" :class="player.hasFinished ? 'border-emerald-400/30 text-emerald-300' : 'border-slate-700 text-slate-400'">
                                        {{ player.hasFinished ? 'Fertig' : player.statusLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 shrink-0 rounded-2xl border border-slate-800 bg-slate-950/60 p-3">
                        <p class="text-xs font-bold text-slate-500">
                            Letztes Update:
                            <span class="text-slate-300">{{ lastUpdatedAt.toLocaleTimeString() }}</span>
                        </p>
                        <p class="mt-1 text-xs font-bold" :class="isLoading ? 'text-amber-300' : 'text-slate-500'">
                            {{ isLoading ? 'Aktualisiere Spielzustand...' : 'Polling aktiv' }}
                        </p>
                        <p v-if="errorMessage" class="mt-2 rounded-xl border border-red-400/30 bg-red-400/10 px-3 py-2 text-sm font-bold text-red-200">
                            {{ errorMessage }}
                        </p>
                    </div>
                </aside>
            </section>
        </div>
    </main>
</template>

<script>
function seatAngle(seatNumber, seatCount) {
    const normalizedSeatCount = Math.max(2, Math.min(11, Number(seatCount || 4)));

    if (seatNumber === 1) {
        return 270;
    }

    const opponentCount = normalizedSeatCount - 1;

    if (opponentCount === 1) {
        return 90;
    }

    const heroSectorDegrees = 70;
    const nonHeroArcDegrees = 360 - heroSectorDegrees;
    const nonHeroArcStartAngle = 305;
    const nonHeroSectorDegrees = nonHeroArcDegrees / opponentCount;
    const index = seatNumber - 2;
    const clockwiseIndex = (opponentCount - 1) - index;

    return nonHeroArcStartAngle + (nonHeroSectorDegrees * clockwiseIndex) + (nonHeroSectorDegrees / 2);
}

export default {
    methods: {
        seatStyle(seatNumber) {
            const angle = seatAngle(Number(seatNumber || 1), Number(this.field?.seatCount || this.players?.length || 4));
            const radians = angle * Math.PI / 180;
            const left = 50 + (43.5 * Math.cos(radians));
            const top = 51 - (39.5 * Math.sin(radians));

            return {
                left: `${left}%`,
                top: `${top}%`,
            };
        },
    },
};
</script>
