<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-lg border border-slate-700 bg-slate-950/70 px-4 py-2 text-xs font-bold uppercase tracking-widest text-slate-200 shadow-sm transition hover:border-amber-400/50 hover:text-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-950 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
