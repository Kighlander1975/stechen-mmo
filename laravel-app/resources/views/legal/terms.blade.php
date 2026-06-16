@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-12 text-slate-100">
        <div class="rounded-2xl border border-amber-500/20 bg-slate-900/80 p-8 shadow-2xl shadow-black/30">
            <p class="mb-4 text-sm font-semibold uppercase tracking-widest text-amber-300">
                Rechtliches
            </p>

            <h1 class="text-3xl font-extrabold text-amber-300">
                Allgemeine Geschäftsbedingungen
            </h1>

            <p class="mt-6 leading-7 text-slate-300">
                Diese Seite befindet sich aktuell im Aufbau.
            </p>

            <p class="mt-4 leading-7 text-slate-300">
                Stechen MMO befindet sich derzeit in einer frühen Entwicklungsphase.
                Echtgeld-, Wallet-, Zahlungs- und Auszahlungsfunktionen sind aktuell nicht aktiv.
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
