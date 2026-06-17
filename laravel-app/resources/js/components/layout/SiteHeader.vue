<script setup>
import { computed, ref } from 'vue';

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
    showWalletPanel: {
        type: Boolean,
        default: false,
    },
    wallet: {
        type: Object,
        default: () => ({
            playMoneyBalanceUnits: 0,
            playMoneyBalanceDisplay: '0 St$',
            realMoneyEnabled: false,
            realMoneyBalanceDisplay: 'Deaktiviert',
            cashierEnabled: false,
        }),
    },
});

const walletMode = ref('play');

const statusToneClasses = {
    neutral: 'border-slate-500/30 bg-slate-500/10 text-slate-300',
    success: 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300',
    warning: 'border-amber-400/30 bg-amber-400/10 text-amber-300',
    danger: 'border-red-400/30 bg-red-400/10 text-red-300',
    admin: 'border-red-400/30 bg-red-400/10 text-red-300',
};

const walletPanelClasses = computed(() => {
    if (walletMode.value === 'real') {
        return 'border-red-400/30 bg-red-500/10 hover:border-red-300/60 hover:bg-red-500/15';
    }

    return 'border-emerald-400/30 bg-emerald-400/10 hover:border-emerald-300/60 hover:bg-emerald-400/15';
});

const walletModeLabel = computed(() => (walletMode.value === 'play' ? 'Spielgeld' : 'Echtgeld'));

const walletBalanceLabel = computed(() => {
    if (walletMode.value === 'play') {
        return props.wallet?.playMoneyBalanceDisplay || '0 St$';
    }

    return props.wallet?.realMoneyBalanceDisplay || 'Deaktiviert';
});

const walletHint = computed(() => {
    if (walletMode.value === 'play') {
        return 'Klick: zu Echtgeld wechseln';
    }

    return 'Klick: zu Spielgeld wechseln';
});

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

function toggleWalletMode() {
    walletMode.value = walletMode.value === 'play' ? 'real' : 'play';
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
            <div class="mx-auto grid max-w-6xl grid-cols-2 items-center gap-6 px-6 py-6">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wide text-amber-400">
                        {{ eyebrow }}
                    </p>

                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-100">
                        {{ title }}
                    </h1>
                </div>

                <div class="flex justify-end">
                    <button
                        v-if="showWalletPanel"
                        type="button"
                        class="group w-full max-w-sm rounded-2xl border px-5 py-4 text-left shadow-xl shadow-black/20 transition focus:outline-none focus:ring-2 focus:ring-amber-300/70"
                        :class="walletPanelClasses"
                        :aria-label="`${walletModeLabel}-Anzeige. ${walletHint}`"
                        @click="toggleWalletMode"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-black uppercase tracking-wide"
                                    :class="walletMode === 'play' ? 'text-emerald-300' : 'text-red-300'"
                                >
                                    {{ walletModeLabel }}
                                </p>

                                <p class="mt-1 text-2xl font-black tracking-tight text-slate-100">
                                    {{ walletBalanceLabel }}
                                </p>

                                <p class="mt-2 text-xs font-medium text-slate-400 transition group-hover:text-slate-300">
                                    {{ walletHint }}
                                </p>
                            </div>

                            <div
                                v-if="walletMode === 'real'"
                                class="flex flex-col items-end gap-2"
                            >
                                <span class="rounded-full border border-red-400/30 bg-red-400/10 px-2 py-1 text-xs font-bold uppercase tracking-wide text-red-300">
                                    inaktiv
                                </span>

                                <span class="rounded-lg border border-slate-700 bg-slate-950/70 px-3 py-1 text-xs font-bold text-slate-500">
                                    Kasse
                                </span>
                            </div>

                            <div
                                v-else
                                class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-2 py-1 text-xs font-bold uppercase tracking-wide text-emerald-300"
                            >
                                aktiv
                            </div>
                        </div>
                    </button>

                    <div
                        v-else-if="statusLabel"
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
