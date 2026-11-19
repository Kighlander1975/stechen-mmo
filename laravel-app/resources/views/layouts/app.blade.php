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

    {{-- fitViewport (z. B. Lobby): Seite genau bildschirmhoch, Inhalt füllt den Rest.
         Ab lg bleibt der Footer sichtbar; darunter app-artig ohne Footer und mit kompaktem Kopf. --}}
    <div @class([
        'min-h-screen bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950',
        'flex h-[100dvh] min-h-0 flex-col lg:h-screen lg:min-h-[720px]' => $fitViewport,
    ])>
        <x-site-header
            :eyebrow="$headerEyebrow ?? null"
            :header-title="$headerTitle ?? null"
            :status-label="$headerStatusLabel ?? null"
            :status-tone="$headerStatusTone ?? null"
            :show-wallet-panel="$showWalletPanel"
            :play-money-balance-units="$playMoneyBalanceUnits"
            :mobile-compact="$fitViewport"
        />

        <main @class([
            'mx-auto w-full max-w-[1600px]',
            'min-h-[calc(100vh-145px)] px-4 py-6 sm:px-6 lg:py-10' => ! $fitViewport,
            'flex min-h-0 flex-1 flex-col lg:px-6 lg:py-10' => $fitViewport,
        ])>
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        <footer @class(['shrink-0 border-t border-slate-800', 'hidden lg:block' => $fitViewport])>
            <div class="mx-auto max-w-[1600px] px-4 py-6 text-sm text-slate-500 sm:px-6">
                &copy; {{ date('Y') }} Stechen-MMO
            </div>
        </footer>
    </div>
</body>
</html>
