@props([
    'headerEyebrow' => null,
    'headerTitle' => null,
    'headerStatusLabel' => null,
    'headerStatusTone' => null,
    'showWalletPanel' => false,
    'playMoneyBalanceUnits' => 0,
    'fitViewport' => false,
])

@php
    $headerEyebrow = $headerEyebrow ?? $attributes->get('header-eyebrow');
    $headerTitle = $headerTitle ?? $attributes->get('header-title');
    $headerStatusLabel = $headerStatusLabel ?? $attributes->get('header-status-label');
    $headerStatusTone = $headerStatusTone ?? $attributes->get('header-status-tone');

    if ($attributes->has('show-wallet-panel')) {
        $showWalletPanel = $attributes->get('show-wallet-panel');
    }

    if ($attributes->has('play-money-balance-units')) {
        $playMoneyBalanceUnits = $attributes->get('play-money-balance-units');
    }

    $showWalletPanel = filter_var($showWalletPanel, FILTER_VALIDATE_BOOLEAN);
    $playMoneyBalanceUnits = (int) $playMoneyBalanceUnits;
@endphp

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
    <x-flash-toast />

    {{-- fitViewport: Seite ab lg genau fensterhoch, Footer bleibt sichtbar, Inhalt füllt den Rest (z. B. Lobby). --}}
    <div @class([
        'min-h-screen bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950',
        'lg:flex lg:h-screen lg:min-h-[720px] lg:flex-col' => $fitViewport,
    ])>
        <x-site-header
            :eyebrow="$headerEyebrow ?? null"
            :header-title="$headerTitle ?? null"
            :status-label="$headerStatusLabel ?? null"
            :status-tone="$headerStatusTone ?? null"
            :show-wallet-panel="$showWalletPanel"
            :play-money-balance-units="$playMoneyBalanceUnits"
        />

        <main @class([
            'mx-auto w-full max-w-[1600px] px-6 py-10',
            'min-h-[calc(100vh-145px)]' => ! $fitViewport,
            'lg:flex lg:min-h-0 lg:flex-1 lg:flex-col' => $fitViewport,
        ])>
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        <footer class="shrink-0 border-t border-slate-800">
            <div class="mx-auto max-w-[1600px] px-6 py-6 text-sm text-slate-500">
                &copy; {{ date('Y') }} Stechen-MMO
            </div>
        </footer>
    </div>
</body>
</html>
