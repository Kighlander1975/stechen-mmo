<section class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
    <p class="text-sm font-medium uppercase tracking-wide text-amber-400">
        Aktueller Account
    </p>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div>
            <p class="text-sm text-slate-400">Name</p>
            <p class="mt-1 font-semibold text-slate-100">{{ $adminAccount['name'] }}</p>
        </div>

        <div>
            <p class="text-sm text-slate-400">E-Mail</p>
            <p class="mt-1 font-semibold text-slate-100">{{ $adminAccount['email'] }}</p>
        </div>

        <div>
            <p class="text-sm text-slate-400">Account</p>
            <p class="mt-1 font-semibold text-slate-100">{{ $adminAccount['displayRole'] }}</p>
        </div>

        <div>
            <p class="text-sm text-slate-400">Spielberechtigung</p>
            <p class="mt-1 font-semibold text-slate-100">
                {{ $adminAccount['canPlayGame'] ? 'Aktiv' : 'Nicht gesetzt' }}
            </p>
        </div>
    </div>

    <div class="mt-6">
        <p class="text-sm text-slate-400">Permissions</p>

        <div class="mt-2 flex flex-wrap gap-2">
            @forelse ($adminAccount['permissions'] as $permission)
                <span class="rounded-full border border-slate-700 bg-slate-950 px-3 py-1 text-xs font-medium text-slate-300">
                    {{ $permission }}
                </span>
            @empty
                <span class="text-sm text-slate-500">Keine Permissions gesetzt.</span>
            @endforelse
        </div>
    </div>

    <div class="mt-6">
        <a
            href="{{ $adminAccount['dashboardUrl'] }}"
            class="inline-flex items-center justify-center rounded-lg border border-slate-700 bg-slate-950/70 px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-amber-400/50 hover:text-amber-300"
        >
            Zurück zum Spielerkonto
        </a>
    </div>
</section>
