@php
    $gameAppProps = [
        'publicCode' => $publicCode,
        'initialState' => $initialState,
        'stateUrl' => $stateUrl,
        'finishUrl' => $finishUrl,
    ];

    $gameAppPropsJson = json_encode(
        $gameAppProps,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );

    $roomName = $initialState['room']['name'] ?? $publicCode;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $roomName }} - Spiel - {{ config('app.name', 'Stechen-MMO') }}</title>

    @vite(['resources/css/app.css', 'resources/js/game.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div
        id="game-app"
        data-props='{{ $gameAppPropsJson }}'
    ></div>
</body>
</html>
