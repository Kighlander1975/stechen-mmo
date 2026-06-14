@extends('layouts.app')

@section('title', 'Sitzung abgelaufen')

@section('content')
<section class="mx-auto max-w-3xl px-6 py-20 text-center">
    <p class="text-sm font-semibold uppercase tracking-widest text-orange-400">
        Fehler 419
    </p>

    <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-100 sm:text-5xl">
        Die Runde ist abgelaufen.
    </h1>

    <p class="mt-6 text-lg leading-8 text-slate-300">
        Deine Sitzung ist abgelaufen oder das Formular war zu lange geöffnet.
        Bitte lade die Seite neu und versuche es erneut.
    </p>

    <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
        <button type="button"
                onclick="window.location.reload()"
                class="rounded-lg bg-amber-400 px-5 py-3 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-amber-300">
            Seite neu laden
        </button>

        <a href="{{ url('/') }}"
           class="rounded-lg border border-slate-600 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">
            Zur Startseite
        </a>
    </div>
</section>
@endsection
