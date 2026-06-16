<script setup>
const props = defineProps({
    brand: {
        type: String,
        default: 'Stechen-MMO',
    },
    brandUrl: {
        type: String,
        default: '/',
    },
    eyebrow: {
        type: String,
        default: 'STECHEN-MMO',
    },
    title: {
        type: String,
        default: 'Stechen-MMO',
    },
    statusLabel: {
        type: String,
        default: 'Gastmodus',
    },
    statusTone: {
        type: String,
        default: 'neutral',
    },
    navItems: {
        type: Array,
        default: () => [],
    },
    logout: {
        type: Object,
        default: null,
    },
});

const statusToneClasses = {
    neutral: 'border-slate-500/30 bg-slate-500/10 text-slate-300',
    success: 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300',
    warning: 'border-amber-400/30 bg-amber-400/10 text-amber-300',
    danger: 'border-red-400/30 bg-red-400/10 text-red-300',
    admin: 'border-red-400/30 bg-red-400/10 text-red-300',
};

function statusClass(tone) {
    return statusToneClasses[tone] || statusToneClasses.neutral;
}

function navClass(item) {
    if (item.tone === 'danger') {
        return 'text-red-300 transition hover:text-red-200';
    }

    if (item.tone === 'primary') {
        return 'rounded-lg border border-amber-400/40 px-3 py-2 text-amber-300 transition hover:bg-amber-400 hover:text-slate-950';
    }

    return 'text-slate-300 transition hover:text-amber-300';
}
</script>

<template>
    <header class="border-b border-slate-800 bg-slate-950/80">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a
                :href="brandUrl"
                class="text-lg font-bold tracking-tight text-amber-400 transition hover:text-amber-300"
            >
                {{ brand }}
            </a>

            <div class="flex flex-wrap items-center justify-end gap-4 text-sm">
                <a
                    v-for="item in navItems"
                    :key="`${item.label}-${item.href}`"
                    :href="item.href"
                    :class="navClass(item)"
                >
                    {{ item.label }}
                </a>

                <form
                    v-if="logout && logout.href && logout.csrf"
                    method="POST"
                    :action="logout.href"
                    class="inline"
                >
                    <input type="hidden" name="_token" :value="logout.csrf">

                    <button
                        type="submit"
                        class="text-slate-300 transition hover:text-amber-300"
                    >
                        {{ logout.label || 'Logout' }}
                    </button>
                </form>
            </div>
        </nav>

        <section class="border-t border-slate-900 bg-slate-900/60">
            <div class="mx-auto max-w-6xl px-6 py-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-wide text-amber-400">
                            {{ eyebrow }}
                        </p>

                        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-100">
                            {{ title }}
                        </h1>
                    </div>

                    <div
                        v-if="statusLabel"
                        class="inline-flex w-fit items-center rounded-full border px-3 py-1 text-sm font-medium"
                        :class="statusClass(statusTone)"
                    >
                        {{ statusLabel }}
                    </div>
                </div>
            </div>
        </section>
    </header>
</template>
