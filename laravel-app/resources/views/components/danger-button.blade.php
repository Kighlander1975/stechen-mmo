<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-lg border border-red-400/40 bg-red-500 px-4 py-2 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-red-400 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 focus:ring-offset-slate-950 active:bg-red-600']) }}>
    {{ $slot }}
</button>
