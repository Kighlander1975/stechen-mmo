<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Stechen-MMO'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div class="min-h-screen bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950">
        <header class="border-b border-slate-800 bg-slate-950/80">
            <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <a href="{{ url('/') }}" class="text-lg font-bold tracking-tight text-amber-400">
                    Stechen-MMO
                </a>

                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ url('/') }}"
                       class="text-slate-300 transition hover:text-amber-300">
                        Start
                    </a>

                    <a href="{{ url('/rules') }}"
                       class="text-slate-300 transition hover:text-amber-300">
                        Regeln
                    </a>

                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="text-slate-300 transition hover:text-amber-300">
                            Dashboard
                        </a>

                        <a href="{{ route('profile.edit') }}"
                           class="text-slate-300 transition hover:text-amber-300">
                            Profil
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf

                            <button type="submit"
                                    class="text-slate-300 transition hover:text-amber-300">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="text-slate-300 transition hover:text-amber-300">
                            Login
                        </a>

                        <a href="{{ route('register') }}"
                           class="rounded-lg border border-amber-400/40 px-3 py-2 text-amber-300 transition hover:bg-amber-400 hover:text-slate-950">
                            Registrieren
                        </a>
                    @endauth
                </div>
            </nav>
        </header>

        @isset($header)
            <section class="border-b border-slate-800 bg-slate-900/60">
                <div class="mx-auto max-w-6xl px-6 py-6">
                    <div class="text-slate-100">
                        {{ $header }}
                    </div>
                </div>
            </section>
        @endisset

        <main class="mx-auto min-h-[calc(100vh-145px)] max-w-6xl px-6 py-10">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        <footer class="border-t border-slate-800">
            <div class="mx-auto max-w-6xl px-6 py-6 text-sm text-slate-500">
                &copy; {{ date('Y') }} Stechen-MMO
            </div>
        </footer>
    </div>
</body>
</html>
