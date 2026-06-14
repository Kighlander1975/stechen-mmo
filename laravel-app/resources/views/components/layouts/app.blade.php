<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'stechen-mmo') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div class="mx-auto max-w-5xl px-6 py-8">
        <header class="mb-8 border-b border-slate-800 pb-4">
            <div class="flex items-center justify-between gap-4">
                <a href="/" class="text-xl font-bold tracking-tight">
                    stechen-mmo
                </a>

                <nav class="flex gap-4 text-sm text-slate-300">
                    <a href="/" class="hover:text-white">Home</a>
                    <a href="/vue-test" class="hover:text-white">Vue-Test</a>
                </nav>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>
    </div>
</body>
</html>
