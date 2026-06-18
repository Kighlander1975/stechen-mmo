@props([
    'eyebrow' => null,
    'headerTitle' => null,
    'statusLabel' => null,
    'statusTone' => null,
    'showWalletPanel' => false,
    'playMoneyBalanceUnits' => 0,
])

@php
    $user = Auth::user();

    $navItems = [
        [
            'label' => 'Start',
            'href' => url('/'),
        ],
        [
            'label' => 'Regeln',
            'href' => url('/rules'),
        ],
    ];

    if ($user) {
        $navItems[] = [
            'label' => 'Dashboard',
            'href' => route('dashboard'),
        ];

        $navItems[] = [
            'label' => 'Lobby',
            'href' => route('lobby'),
        ];

        if ($user->hasPermission('admin.access')) {
            $navItems[] = [
                'label' => 'Admin',
                'href' => route('admin.dashboard'),
                'tone' => 'danger',
            ];
        }

        $navItems[] = [
            'label' => 'Profil',
            'href' => route('profile.edit'),
        ];
    } else {
        $navItems[] = [
            'label' => 'Login',
            'href' => route('login'),
        ];

        $navItems[] = [
            'label' => 'Registrieren',
            'href' => route('register'),
            'tone' => 'primary',
        ];
    }

    $showWalletPanel = $user
        ? true
        : filter_var($showWalletPanel, FILTER_VALIDATE_BOOLEAN);

    $playMoneyBalanceUnits = (int) $playMoneyBalanceUnits;

    if ($user && $playMoneyBalanceUnits === 0) {
        $playMoneyBalanceUnits = (int) (\App\Models\Wallet::query()
            ->where('user_id', $user->id)
            ->where('wallet_type', \App\Models\Wallet::TYPE_USER)
            ->where('asset_type', \App\Models\Wallet::ASSET_PLAY_MONEY)
            ->where('currency_code', \App\Models\Wallet::CURRENCY_STECHEN_DOLLAR)
            ->value('balance_units') ?? 0);
    }

    $playMoneyBalanceDisplay = number_format($playMoneyBalanceUnits, 0, ',', '.').' St$';

    $headerProps = [
        'brand' => 'Stechen-MMO',
        'brandUrl' => url('/'),
        'eyebrow' => $eyebrow ?: ($user ? 'SPIELERKONTO' : 'STECHEN-MMO'),
        'title' => $headerTitle ?: ($user ? 'Willkommen, '.$user->name : 'Willkommen bei Stechen-MMO'),
        'statusLabel' => $statusLabel ?: ($user ? 'Konto aktiv' : 'Gastmodus'),
        'statusTone' => $statusTone ?: ($user ? 'success' : 'neutral'),
        'navItems' => $navItems,
        'showWalletPanel' => $showWalletPanel,
        'wallet' => [
            'playMoneyBalanceUnits' => $playMoneyBalanceUnits,
            'playMoneyBalanceDisplay' => $playMoneyBalanceDisplay,
            'realMoneyEnabled' => false,
            'realMoneyBalanceDisplay' => 'Deaktiviert',
            'cashierEnabled' => false,
        ],
        'logout' => $user ? [
            'label' => 'Logout',
            'href' => route('logout'),
            'csrf' => csrf_token(),
        ] : null,
    ];

    $json = json_encode(
        $headerProps,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );
@endphp

<div
    data-vue-component="site-header"
    data-props='{{ $json }}'
></div>

<noscript>
    <header class="border-b border-slate-800 bg-slate-950/80">
        <nav class="mx-auto flex max-w-[1600px] items-center justify-between px-6 py-4">
            <a href="{{ url('/') }}" class="text-lg font-bold tracking-tight text-amber-400">
                Stechen-MMO
            </a>

            <div class="flex items-center gap-4 text-sm">
                @foreach ($navItems as $item)
                    <a href="{{ $item['href'] }}" class="text-slate-300">
                        {{ $item['label'] }}
                    </a>
                @endforeach

                @if ($user)
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-slate-300">
                            Logout
                        </button>
                    </form>
                @endif
            </div>
        </nav>

        <section class="border-t border-slate-900 bg-slate-900/60">
            <div class="mx-auto grid max-w-[1600px] grid-cols-2 items-center gap-6 px-6 py-6">
                <div>
                    <p class="text-sm font-medium uppercase tracking-wide text-amber-400">
                        {{ $headerProps['eyebrow'] }}
                    </p>

                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-100">
                        {{ $headerProps['title'] }}
                    </h1>
                </div>

                @if ($showWalletPanel)
                    <div class="flex justify-end">
                        <div class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-5 py-4 text-right">
                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-300">
                                Spielgeld
                            </p>
                            <p class="mt-1 text-2xl font-black text-slate-100">
                                {{ $playMoneyBalanceDisplay }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                JavaScript aktiviert den Umschalter.
                            </p>
                        </div>
                    </div>
                @elseif ($headerProps['statusLabel'])
                    <div class="flex justify-end">
                        <div class="inline-flex w-fit items-center rounded-full border px-3 py-1 text-sm font-medium">
                            {{ $headerProps['statusLabel'] }}
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </header>
</noscript>
