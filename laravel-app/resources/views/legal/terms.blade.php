@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-4xl py-2 text-slate-100 sm:px-4 sm:py-12">
        <div class="rounded-2xl border border-amber-500/20 bg-slate-900/80 p-5 shadow-2xl sm:p-8 shadow-black/30">
            <p class="mb-4 text-sm font-semibold uppercase tracking-widest text-amber-300">
                Rechtliches
            </p>

            <h1 class="hyphens-auto break-words text-2xl font-extrabold text-amber-300 sm:text-3xl">
                Allgemeine Geschäftsbedingungen
            </h1>

            <p class="mt-6 leading-7 text-slate-300">
                Diese Seite befindet sich aktuell im Aufbau.
            </p>

            <p class="mt-4 leading-7 text-slate-300">
                Stechen MMO befindet sich derzeit in einer frühen Entwicklungsphase.
                Spielgeld (St$) ist ein virtuelles Spielguthaben ohne Geldwert.
            </p>

            <div class="mt-8 rounded-lg border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm leading-6 text-amber-100">
                Hinweis: Vor einer produktiven Veröffentlichung werden die AGB vollständig ausgearbeitet,
                geprüft und versioniert.
            </div>

            <div class="mt-8">
                <a href="{{ url('/') }}" class="font-medium text-amber-300 underline-offset-4 transition hover:text-amber-200 hover:underline">
                    Zurück zur Startseite
                </a>
            </div>
        </div>
    </section>
@endsection
