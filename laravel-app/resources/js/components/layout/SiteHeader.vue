<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

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
    devMode: {
        type: Boolean,
        default: false,
    },
    userName: {
        type: String,
        default: '',
    },
    // Nur Seiten mit fit-viewport (Lobby): unter lg zusätzlich ohne Seitentitel.
    mobileCompact: {
        type: Boolean,
        default: false,
    },
    wallet: {
        type: Object,
        default: () => ({
            playMoneyBalanceUnits: 0,
            playMoneyBalanceDisplay: '0 St$',
        }),
    },
});

const walletState = ref({ ...(props.wallet || {}) });
const mobileMenuOpen = ref(false);

const statusToneClasses = {
    neutral: 'border-slate-500/30 bg-slate-500/10 text-slate-300',
    success: 'border-emerald-400/30 bg-emerald-400/10 text-emerald-300',
    warning: 'border-amber-400/30 bg-amber-400/10 text-amber-300',
    danger: 'border-red-400/30 bg-red-400/10 text-red-300',
    admin: 'border-red-400/30 bg-red-400/10 text-red-300',
};

const walletBalanceLabel = computed(() => {
    return walletState.value?.primaryDisplay
        || walletState.value?.availableDisplay
        || walletState.value?.playMoneyBalanceDisplay
        || '0 St$';
});

const walletReservedLabel = computed(() => {
    return (walletState.value?.reservedUnits || 0) > 0
        ? `${walletState.value.reservedDisplay} reserviert`
        : '';
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

function handleWalletUpdated(event) {
    if (!event?.detail) {
        return;
    }

    walletState.value = {
        ...walletState.value,
        ...event.detail,
    };
}

onMounted(() => {
    window.addEventListener('stechen:wallet-updated', handleWalletUpdated);
});

onUnmounted(() => {
    window.removeEventListener('stechen:wallet-updated', handleWalletUpdated);
});
</script>

<template>
    <header class="relative shrink-0 border-b border-slate-800 bg-slate-950/80">
        <!-- Kompakter Handy-Kopf (unter lg, alle Seiten): Logo, Name, Spielgeld bzw. Registrieren, Menü-Knopf -->
        <div class="flex items-center gap-3 px-4 py-2.5 lg:hidden">
            <div class="min-w-0">
                <a :href="brandUrl" class="block whitespace-nowrap text-lg font-bold leading-tight tracking-tight text-amber-400">{{ brand }}</a>
                <p v-if="userName" class="truncate text-sm leading-tight text-slate-300">{{ userName }}</p>
            </div>

            <div
                v-if="showWalletPanel"
                class="ml-auto rounded-xl border border-emerald-400/40 px-3 py-1 text-right leading-tight tabular-nums"
            >
                <p class="text-base font-bold text-slate-100">{{ walletBalanceLabel }}</p>
                <p v-if="walletReservedLabel" class="text-xs text-slate-400">{{ walletReservedLabel }}</p>
            </div>

            <a
                v-for="item in (showWalletPanel ? [] : navItems.filter((navItem) => navItem.tone === 'primary'))"
                :key="`bar-${item.href}`"
                :href="item.href"
                class="ml-auto whitespace-nowrap rounded-lg border border-amber-400/50 px-3 py-1.5 text-sm text-amber-300"
            >
                {{ item.label }}
            </a>

            <button
                type="button"
                class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-700 text-xl text-slate-300"
                :class="{ 'ml-auto': !showWalletPanel && !navItems.some((navItem) => navItem.tone === 'primary') }"
                :aria-expanded="mobileMenuOpen"
                aria-label="Menü"
                @click="mobileMenuOpen = !mobileMenuOpen"
            >
                {{ mobileMenuOpen ? '×' : '☰' }}
            </button>

            <!-- Das Menü gleitet unter dem Kopf hervor nach unten (der Rahmen schneidet es oben ab). -->
            <div class="absolute inset-x-0 top-full z-50 overflow-hidden">
                <Transition
                    enter-active-class="transition duration-300 ease-out motion-reduce:transition-none"
                    enter-from-class="-translate-y-full opacity-0"
                    leave-active-class="transition duration-200 ease-in motion-reduce:transition-none"
                    leave-to-class="-translate-y-full opacity-0"
                >
                    <div
                        v-if="mobileMenuOpen"
                        class="flex flex-col border-b border-slate-700 bg-[#17223a] px-4 py-2 shadow-2xl shadow-black/50"
                    >
                        <p v-if="userName" class="border-b border-slate-700/70 py-3 text-sm text-slate-400">
                            Angemeldet als <b class="text-slate-100">{{ userName }}</b>
                        </p>

                        <a
                            v-for="item in navItems"
                            :key="`mobile-${item.label}-${item.href}`"
                            :href="item.href"
                            class="border-b border-slate-700/70 py-3 text-base"
                            :class="navClass(item)"
                        >
                            {{ item.label }}
                        </a>

                        <form
                            v-if="logout && logout.href && logout.csrf"
                            method="POST"
                            :action="logout.href"
                        >
                            <input type="hidden" name="_token" :value="logout.csrf">
                            <button type="submit" class="w-full py-3 text-left text-base text-slate-300">
                                {{ logout.label || 'Logout' }}
                            </button>
                        </form>
                    </div>
                </Transition>
            </div>
        </div>

        <nav class="mx-auto hidden max-w-[1600px] items-center justify-between px-6 py-4 lg:flex">
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

        <section
            class="border-t border-slate-900 bg-slate-900/60"
            :class="{ 'hidden lg:block': mobileCompact }"
        >
            <!-- Handy: nur Eyebrow und Titel; Info-, Spielgeld- und Status-Kasten erst ab lg. -->
            <div class="mx-auto grid max-w-[1600px] grid-cols-1 items-center gap-5 px-4 py-4 lg:grid-cols-2 lg:px-6 lg:py-5">
                <div class="min-w-0">
                    <p class="text-sm font-medium uppercase tracking-wide text-amber-400">
                        {{ eyebrow }}
                    </p>

                    <h1 class="mt-1 break-words text-xl font-bold tracking-tight text-slate-100 [overflow-wrap:anywhere] lg:text-2xl">
                        {{ title }}
                    </h1>
                </div>

                <div class="hidden w-full justify-end gap-3 lg:flex">
                    <div
                        v-if="showWalletPanel"
                        class="min-h-[5.5rem] min-w-0 flex-1 basis-0 rounded-2xl border border-slate-700 bg-slate-900/70 px-5 py-3 shadow-lg shadow-black/10"
                    >
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                            Info
                        </p>
                        <p class="mt-1 text-sm italic text-slate-500">
                            Info-Platzhalter
                        </p>
                    </div>

                    <div
                        v-if="showWalletPanel"
                        class="min-w-0 flex-1 basis-0 rounded-2xl border border-emerald-400/35 bg-slate-900/70 px-5 py-3 shadow-lg shadow-black/15"
                    >
                        <p class="truncate text-xs font-bold uppercase tracking-wider text-emerald-300">
                            Spielgeld verfügbar
                        </p>

                        <p class="mt-0.5 truncate text-2xl font-bold tabular-nums text-slate-100">
                            {{ walletBalanceLabel }}
                        </p>

                        <p v-if="walletReservedLabel" class="truncate text-sm tabular-nums text-slate-400">
                            {{ walletReservedLabel }}
                        </p>

                        <span
                            v-if="devMode"
                            class="mt-1 inline-block max-w-full truncate rounded-full border border-dashed border-amber-300/40 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-amber-200/80"
                            title="Nur im Dev-Modus sichtbar: Platz für spätere Bonus-Informationen"
                        >
                            Login-Bonus: vorbereitet
                        </span>
                    </div>

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

