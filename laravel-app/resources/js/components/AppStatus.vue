<script setup>
defineProps({
    appName: {
        type: String,
        default: 'stechen-mmo',
    },
});
</script>

<template>
    <section class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-emerald-900">
        <h2 class="text-lg font-semibold">Vue 3 ist aktiv</h2>
        <p class="mt-2 text-sm">
            Die Vue-Insel wurde erfolgreich über Vite in Laravel geladen.
        </p>
        <p class="mt-2 text-sm">
            App: <strong>{{ appName }}</strong>
        </p>
    </section>
</template>
