<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Stechen MMO') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-950 text-slate-100">
        <x-flash-toast />
        <div class="min-h-screen relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-amber-950/40"></div>
            <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-amber-500/10 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-orange-500/10 blur-3xl"></div>

            <main class="relative min-h-screen flex flex-col items-center justify-center px-4 py-10">
                <div class="w-full max-w-md">
                    <div class="mb-8 text-center">
                        <a href="/" class="inline-flex flex-col items-center gap-4 group">
                            <x-application-logo class="h-20 w-20 text-amber-400 transition group-hover:text-amber-300" />

                            <span class="block text-3xl font-extrabold tracking-widest text-amber-300 uppercase">
                                Stechen MMO
                            </span>

                            <span class="block text-sm text-slate-400">
                                Accountzugang zur Spielwelt
                            </span>
                        </a>
                    </div>

                    <section class="rounded-2xl border border-amber-500/20 bg-slate-900/90 px-6 py-6 shadow-2xl shadow-black/40 backdrop-blur sm:px-8">
                        {{ $slot }}
                    </section>

                    <nav class="mt-6 flex items-center justify-center gap-4 text-sm text-slate-400">
                        <a href="/" class="transition hover:text-amber-300">
                            Startseite
                        </a>

                        <span class="text-slate-700">|</span>

                        <a href="/rules" class="transition hover:text-amber-300">
                            Regeln
                        </a>
                    </nav>

                    <p class="mt-6 text-center text-xs leading-5 text-slate-500">
                        Hinweis: Echtgeld- und Wallet-Funktionen sind in dieser Entwicklungsphase noch nicht aktiv.
                    </p>
                </div>
            </main>
        </div>
    </body>
</html>


