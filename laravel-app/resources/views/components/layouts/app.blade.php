<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'stechen-mmo') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 antialiased">
    <div class="mx-auto max-w-5xl px-6 py-8">
        <header class="mb-8 border-b border-slate-700 pb-4">
            <div class="flex items-center justify-between gap-4">
                <a href="/" class="text-xl font-bold tracking-tight text-white hover:text-emerald-300">
                    stechen-mmo
                </a>

                <nav class="flex gap-4 text-sm text-slate-200">
                    <a href="/" class="hover:text-emerald-300">Home</a>
                    <a href="/rules" class="hover:text-emerald-300">Regeln</a>
                    <a href="/vue-test" class="hover:text-emerald-300">Vue-Test</a>
                </nav>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="mt-12 border-t border-slate-700 pt-4 text-xs text-slate-400">
            Stechen-MMO · Laravel · Vue 3 · Vite
        </footer>
    </div>
</body>
</html>
