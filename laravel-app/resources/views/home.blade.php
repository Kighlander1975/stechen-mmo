<x-layouts.app
    title="Stechen-MMO"
    header-eyebrow="Startseite"
    :header-title="Auth::check() ? 'Willkommen bei Stechen-MMO, '.Auth::user()->name : 'Willkommen bei Stechen-MMO'"
>
    <section class="space-y-10">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-8 shadow-xl shadow-black/20">
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-300">
                Browserbasiertes Kartenspiel
            </p>

            <h2 class="mt-3 text-4xl font-bold tracking-tight text-white">
                Stechen-MMO
            </h2>

            <p class="mt-4 max-w-2xl text-lg text-slate-200">
                Ein rundenbasiertes Online-Kartenspiel mit Laravel, Vue 3 und einer klaren,
                erweiterbaren Projektstruktur.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="/rules" class="rounded-lg bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-950 hover:bg-emerald-300">
                    Regeln ansehen
                </a>

                <a href="/vue-test" class="rounded-lg border border-slate-500 bg-slate-700 px-4 py-2 text-sm font-semibold text-slate-100 hover:border-emerald-300 hover:text-emerald-200">
                    Vue-Test öffnen
                </a>
            </div>
        </div>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 shadow-md">
                <h2 class="font-semibold text-white">Foundation</h2>
                <p class="mt-2 text-sm text-slate-300">
                    Laravel, Datenbank, Vite, Tailwind und Vue 3 sind eingerichtet.
                </p>
            </article>

            <article class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 shadow-md">
                <h2 class="font-semibold text-white">Spielsystem</h2>
                <p class="mt-2 text-sm text-slate-300">
                    Die Spiellogik wird schrittweise als Service-Struktur aufgebaut.
                </p>
            </article>

            <article class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 shadow-md">
                <h2 class="font-semibold text-white">Mehrspieler</h2>
                <p class="mt-2 text-sm text-slate-300">
                    Multiplayer, Lobby und Fallback-fähige Architektur folgen in späteren Phasen.
                </p>
            </article>
        </section>
    </section>
</x-layouts.app>
