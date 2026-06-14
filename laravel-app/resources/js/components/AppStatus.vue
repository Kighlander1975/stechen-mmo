<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    appName: {
        type: String,
        default: 'stechen-mmo',
    },
});

const loading = ref(true);
const error = ref(null);
const status = ref(null);

onMounted(async () => {
    try {
        const response = await fetch('/api/app-status', {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        status.value = await response.json();
    } catch (fetchError) {
        error.value = fetchError.message || 'Unbekannter Fehler';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <section class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-emerald-900">
        <h2 class="text-lg font-semibold">Vue 3 ist aktiv</h2>

        <p class="mt-2 text-sm">
            Die Vue-Insel wurde erfolgreich über Vite in Laravel geladen.
        </p>

        <p class="mt-2 text-sm">
            App aus Blade: <strong>{{ appName }}</strong>
        </p>

        <div class="mt-4 rounded-md border border-emerald-200 bg-white/70 p-3 text-sm">
            <p v-if="loading">
                Lade Laravel JSON-Status …
            </p>

            <p v-else-if="error" class="text-red-700">
                Fehler beim Abruf des JSON-Endpunkts: {{ error }}
            </p>

            <div v-else>
                <p>
                    JSON-Endpunkt: <strong>{{ status.status }}</strong>
                </p>
                <p>
                    App aus Laravel: <strong>{{ status.app }}</strong>
                </p>
                <p>
                    Umgebung: <strong>{{ status.environment }}</strong>
                </p>
                <p>
                    Version: <strong>{{ status.version }}</strong>
                </p>
            </div>
        </div>
    </section>
</template>
