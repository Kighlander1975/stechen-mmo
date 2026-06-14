@extends('layouts.app')

@section('title', 'Seite nicht gefunden')

@section('content')
<section class="mx-auto max-w-3xl px-6 py-20 text-center">
    <p class="text-sm font-semibold uppercase tracking-widest text-amber-400">
        Fehler 404
    </p>

    <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-100 sm:text-5xl">
        Diese Karte liegt nicht im Stapel.
    </h1>

    <p class="mt-6 text-lg leading-8 text-slate-300">
        Die angeforderte Seite wurde nicht gefunden. Vielleicht wurde sie verschoben,
        ist noch nicht freigeschaltet oder der Link enthält einen Tippfehler.
    </p>

    <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
        <a href="{{ url('/') }}"
           class="rounded-lg bg-amber-400 px-5 py-3 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-amber-300">
            Zur Startseite
        </a>

        <a href="{{ url('/rules') }}"
           class="rounded-lg border border-slate-600 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:bg-slate-800">
            Regeln ansehen
        </a>
    </div>
</section>
@endsection
