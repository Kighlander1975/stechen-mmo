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
</script>

<template>
    <section
        class="rounded-2xl border border-sky-400/20 bg-sky-400/5 px-4 py-2 text-xs text-sky-100"
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
    </section>
</template>
