@php
    $flashMessage = session('status') ?? session('success') ?? session('error') ?? session('warning') ?? session('info');

    $flashType = 'success';

    if (session('error')) {
        $flashType = 'error';
    } elseif (session('warning')) {
        $flashType = 'warning';
    } elseif (session('info')) {
        $flashType = 'info';
    }

    $toastClasses = [
        'success' => 'border-emerald-400/40 bg-slate-950/95 text-emerald-100 shadow-emerald-950/40',
        'error' => 'border-red-400/40 bg-red-950/95 text-red-100 shadow-red-950/40',
        'warning' => 'border-amber-400/40 bg-amber-950/95 text-amber-100 shadow-amber-950/40',
        'info' => 'border-sky-400/40 bg-sky-950/95 text-sky-100 shadow-sky-950/40',
    ];

    $toastIcons = [
        'success' => '✓',
        'error' => '!',
        'warning' => '!',
        'info' => 'i',
    ];
@endphp

@if ($flashMessage)
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 4500)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-250"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        class="fixed right-4 top-4 z-50 w-[calc(100%-2rem)] max-w-sm rounded-2xl border px-5 py-4 shadow-2xl backdrop-blur sm:right-6 sm:top-6 {{ $toastClasses[$flashType] }}"
        role="status"
        aria-live="polite"
    >
        <div class="flex items-start gap-3">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-current/30 text-sm font-bold">
                {{ $toastIcons[$flashType] }}
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold">
                    {{ $flashType === 'success' ? 'Erfolg' : ($flashType === 'error' ? 'Fehler' : ($flashType === 'warning' ? 'Hinweis' : 'Info')) }}
                </p>

                <p class="mt-1 text-sm leading-relaxed text-current/85">
                    {{ $flashMessage }}
                </p>
            </div>

            <button
                type="button"
                x-on:click="show = false"
                class="ml-2 rounded-lg px-2 text-xl leading-none text-current/60 transition hover:bg-white/10 hover:text-current"
                aria-label="Meldung schließen"
            >
                ×
            </button>
        </div>
    </div>
@endif
