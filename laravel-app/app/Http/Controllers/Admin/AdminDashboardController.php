<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\Phase3\Phase3LocalTestHarnessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request, Phase3LocalTestHarnessService $phase3LocalTestHarness): View
    {
        $user = $request->user();

        $roomSupplyTestModeEnabled = SystemSetting::roomSupplyIgnoreWalletEligibilityIsEnabled();
        $roomSupplyTestModeExpiresAt = SystemSetting::roomSupplyIgnoreWalletEligibilityExpiresAt();
        $roomSupplyTestModeIsLocal = app()->environment(['local', 'testing']);
        $roomSupplyTestModeExpiry = $roomSupplyTestModeExpiresAt
            ? Carbon::parse($roomSupplyTestModeExpiresAt)
            : null;

        return view('admin.dashboard', [
            'adminAccount' => [
                'name' => $user->name,
                'email' => $user->email,
                'displayRole' => $user->accountDisplayRole(),
                'canPlayGame' => $user->canPlayGame(),
                'permissions' => $user->permissions ?? [],
                'dashboardUrl' => route('dashboard'),
            ],

            'adminNavigation' => [
                'registrationBonusBackfillUrl' => route('admin.rewards.registration-bonus-backfill.index'),
            ],

            'roomSupplyTestMode' => [
                'environment' => app()->environment(),
                'isLocal' => $roomSupplyTestModeIsLocal,
                'enabled' => $roomSupplyTestModeEnabled,
                'expiresAt' => $roomSupplyTestModeExpiresAt,
                'expiry' => $roomSupplyTestModeExpiry,
                'active' => $roomSupplyTestModeIsLocal
                    && $roomSupplyTestModeEnabled
                    && $roomSupplyTestModeExpiry !== null
                    && $roomSupplyTestModeExpiry->isFuture(),
                'enableUrl' => route('admin.game-rooms.supply-test-mode.enable'),
                'disableUrl' => route('admin.game-rooms.supply-test-mode.disable'),
            ],

            'phase3LocalTestHarness' => [
                'environment' => app()->environment(),
                'available' => $phase3LocalTestHarness->isAvailableInCurrentEnvironment(),
                'enabled' => $phase3LocalTestHarness->isEnabled(),
                'testUserEmailDomain' => $phase3LocalTestHarness->testUserEmailDomain(),
                'enableUrl' => route('admin.phase3-local-test-harness.enable'),
                'disableUrl' => route('admin.phase3-local-test-harness.disable'),
            ],
        ]);
    }
}
