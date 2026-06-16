@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border border-slate-700 bg-slate-950/70 text-slate-100 shadow-sm placeholder:text-slate-500 focus:border-amber-400 focus:ring-amber-400 disabled:cursor-not-allowed disabled:opacity-60']) }}>
