<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Phase3\Phase3LocalTestDataService;
use App\Services\Phase3\Phase3LocalTestHarnessService;
use Illuminate\Http\RedirectResponse;

class Phase3LocalTestHarnessController extends Controller
{
    public function enable(
        Phase3LocalTestHarnessService $phase3LocalTestHarness,
        Phase3LocalTestDataService $phase3LocalTestData,
    ): RedirectResponse {
        if (! $phase3LocalTestHarness->isAvailableInCurrentEnvironment()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('status', 'Der lokale Phase-3-Browser-Testmodus kann nur in local/testing aktiviert werden.');
        }

        $result = $phase3LocalTestData->activate();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Lokaler Phase-3-Browser-Testmodus wurde aktiviert. '.count($result['users']).' Testuser und '.count($result['rooms']).' Testräume wurden frisch vorbereitet. Passwort: password');
    }

    public function disable(Phase3LocalTestDataService $phase3LocalTestData): RedirectResponse
    {
        $cleanup = $phase3LocalTestData->deactivate();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Lokaler Phase-3-Browser-Testmodus wurde deaktiviert. Testdaten wurden bereinigt: '.$cleanup['users'].' User, '.$cleanup['wallets'].' Wallets, '.$cleanup['ledger_entries'].' Ledger-Einträge, '.$cleanup['rooms'].' Räume.');
    }
}
