@extends('layouts.app')

@section('title', 'Serverfehler')

@section('content')
<section class="mx-auto max-w-3xl px-6 py-20 text-center">
    <p class="text-sm font-semibold uppercase tracking-widest text-red-400">
        Fehler 500
    </p>

    <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-100 sm:text-5xl">
        Am Spieltisch ist etwas umgefallen.
    </h1>

    <p class="mt-6 text-lg leading-8 text-slate-300">
        Es ist ein unerwarteter Serverfehler aufgetreten.
        Bitte versuche es später erneut.
    </p>

    <div class="mt-10">
        <a href="{{ url('/') }}"
           class="rounded-lg bg-amber-400 px-5 py-3 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-amber-300">
            Zur Startseite
        </a>
    </div>
</section>
@endsection
