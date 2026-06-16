<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-lg border border-amber-400/40 bg-amber-400 px-4 py-2 text-xs font-bold uppercase tracking-widest text-slate-950 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-slate-950 active:bg-amber-500']) }}>
    {{ $slot }}
</button>
