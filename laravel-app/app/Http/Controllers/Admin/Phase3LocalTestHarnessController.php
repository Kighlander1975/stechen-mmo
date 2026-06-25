<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Phase3\Phase3LocalTestDataService;
use App\Services\Phase3\Phase3LocalTestHarnessService;
use Illuminate\Http\RedirectResponse;

class Phase3LocalTestHarnessController extends Controller
{
    public function enable(Phase3LocalTestHarnessService $phase3LocalTestHarness): RedirectResponse
    {
        if (! $phase3LocalTestHarness->isAvailableInCurrentEnvironment()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('status', 'Der lokale Phase-3-Browser-Testmodus kann nur in local/testing aktiviert werden.');
        }

        $phase3LocalTestHarness->enable();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Lokaler Phase-3-Browser-Testmodus wurde aktiviert.');
    }

    public function disable(Phase3LocalTestHarnessService $phase3LocalTestHarness): RedirectResponse
    {
        $phase3LocalTestHarness->disable();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Lokaler Phase-3-Browser-Testmodus wurde deaktiviert.');
    }

    public function prepareTestUsers(
        Phase3LocalTestHarnessService $phase3LocalTestHarness,
        Phase3LocalTestDataService $phase3LocalTestData,
    ): RedirectResponse {
        if (! $phase3LocalTestHarness->isEnabled()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('status', 'Der lokale Phase-3-Browser-Testmodus muss aktiv sein, bevor Testuser vorbereitet werden.');
        }

        $preparedUsers = $phase3LocalTestData->prepareUsers();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', count($preparedUsers).' lokale Phase-3-Testuser wurden vorbereitet. Passwort: password');
    }
}
