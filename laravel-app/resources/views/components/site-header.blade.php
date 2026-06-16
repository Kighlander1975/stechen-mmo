@props([
    'eyebrow' => null,
    'headerTitle' => null,
    'statusLabel' => null,
    'statusTone' => null,
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

    $headerProps = [
        'brand' => 'Stechen-MMO',
        'brandUrl' => url('/'),
        'eyebrow' => $eyebrow ?: ($user ? 'SPIELERKONTO' : 'STECHEN-MMO'),
        'title' => $headerTitle ?: ($user ? 'Willkommen, '.$user->name : 'Willkommen bei Stechen-MMO'),
        'statusLabel' => $statusLabel ?: ($user ? 'Konto aktiv' : 'Gastmodus'),
        'statusTone' => $statusTone ?: ($user ? 'success' : 'neutral'),
        'navItems' => $navItems,
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
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
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
            <div class="mx-auto max-w-6xl px-6 py-6">
                <p class="text-sm font-medium uppercase tracking-wide text-amber-400">
                    {{ $headerProps['eyebrow'] }}
                </p>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-100">
                    {{ $headerProps['title'] }}
                </h1>
            </div>
        </section>
    </header>
</noscript>
