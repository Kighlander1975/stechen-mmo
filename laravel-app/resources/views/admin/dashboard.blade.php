<x-app-layout
    header-eyebrow="Administration"
    header-title="Admin-Dashboard"
    header-status-label="Zugriff über Permission: admin.access"
    header-status-tone="admin"
>
    <div class="space-y-8">
        @include('admin.dashboard.sections.protected-notice')

        @include('admin.dashboard.sections.rewards-card', [
            'registrationBonusBackfillUrl' => $adminNavigation['registrationBonusBackfillUrl'],
        ])

        @include('admin.dashboard.sections.room-supply-test-mode', [
            'roomSupplyTestMode' => $roomSupplyTestMode,
        ])

        @include('admin.dashboard.sections.phase3-local-test-harness', [
            'phase3LocalTestHarness' => $phase3LocalTestHarness,
        ])

        @include('admin.dashboard.sections.overview-cards')

        @include('admin.dashboard.sections.current-account', [
            'adminAccount' => $adminAccount,
        ])
    </div>
</x-app-layout>
