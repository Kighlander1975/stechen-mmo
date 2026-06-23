<script setup>
import { computed } from 'vue';

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

const selectedLabel = computed(() => {
    if (!props.selectedRoomCode) {
        return 'Keine Auswahl';
    }

    return props.selectedRoomVisible
        ? `Auswahl: ${props.selectedRoomCode}`
        : 'Auswahl durch Filter ausgeblendet';
});

const activeFilterCount = computed(() => Object.values(props.filters || {}).filter(Boolean).length);

const selectedRoomDetails = computed(() => {
    if (!props.selectedRoomVisible || !props.selectedRoom) {
        return null;
    }

    return props.selectedRoom;
});
</script>

<template>
    <section
        class="rounded-2xl border border-sky-400/20 bg-sky-400/5 px-4 py-3 text-xs text-sky-100"
        aria-label="Vue Lobby-Raumbrowser Status"
    >
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="font-bold uppercase tracking-wide text-sky-300">
                Vue-Raumbrowser bereit
            </p>

            <p class="text-sky-100/80">
                {{ roomCount }} Räume · {{ selectedLabel }} · {{ activeFilterCount }} aktive Filter
            </p>
        </div>

        <article
            v-if="selectedRoomDetails"
            class="mt-3 rounded-2xl border border-amber-400/30 bg-slate-950/60 p-3 text-slate-100"
            aria-label="Vue Raumdetails"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[0.65rem] font-black uppercase tracking-wide text-amber-300">
                        Vue-Detailkarte
                    </p>

                    <h3 class="mt-1 truncate text-base font-black tracking-tight">
                        {{ selectedRoomDetails.name }}
                    </h3>

                    <p class="mt-0.5 truncate font-mono text-[0.65rem] font-semibold text-slate-500">
                        {{ selectedRoomDetails.publicCode }}
                    </p>
                </div>

                <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-2.5 py-1 text-[0.65rem] font-black uppercase tracking-wide text-emerald-300">
                    {{ selectedRoomDetails.statusDisplay }}
                </span>
            </div>

            <dl class="mt-3 grid gap-2 sm:grid-cols-5">
                <div class="rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2">
                    <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">
                        Buy-in
                    </dt>
                    <dd class="mt-1 truncate font-semibold text-slate-200">
                        {{ selectedRoomDetails.buyInDisplay }}
                    </dd>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2">
                    <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">
                        Spieler
                    </dt>
                    <dd class="mt-1 truncate font-semibold text-slate-200">
                        {{ selectedRoomDetails.playersDisplay }}
                    </dd>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2">
                    <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">
                        Start
                    </dt>
                    <dd class="mt-1 truncate font-semibold text-slate-200">
                        {{ selectedRoomDetails.startDisplay }}
                    </dd>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2">
                    <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-slate-500">
                        Status
                    </dt>
                    <dd class="mt-1 truncate font-semibold text-slate-200">
                        {{ selectedRoomDetails.statusDisplay }}
                    </dd>
                </div>

                <div class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-3 py-2">
                    <dt class="text-[0.58rem] font-black uppercase leading-none tracking-wide text-emerald-300">
                        Gewinnpool
                    </dt>
                    <dd class="mt-1 truncate font-black text-emerald-100">
                        {{ selectedRoomDetails.prizePoolDisplay }}
                    </dd>
                    <dd class="mt-0.5 truncate text-[0.58rem] leading-none text-emerald-100/45">
                        {{ selectedRoomDetails.feeDisplay }}
                    </dd>
                </div>
            </dl>
        </article>

        <article
            v-else
            class="mt-3 rounded-2xl border border-dashed border-slate-700 bg-slate-950/40 p-3 text-slate-400"
            aria-label="Vue Raumdetails leer"
        >
            Kein Raum für die Vue-Detailkarte ausgewählt.
        </article>
    </section>
</template>
