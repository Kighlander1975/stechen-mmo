<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class RoomSupplyTestModeController extends Controller
{
    public function enable(): RedirectResponse
    {
        if (! app()->environment(['local', 'testing'])) {
            return redirect()
                ->route('admin.dashboard')
                ->with('status', 'Room-Supply-Testmodus kann nur in local/testing aktiviert werden.');
        }

        SystemSetting::setValue(
            SystemSetting::KEY_ROOM_SUPPLY_IGNORE_WALLET_ELIGIBILITY_ENABLED,
            '1',
            'Erlaubt Room-Supply im Entwicklungsmodus ohne Wallet-Eligibility.',
        );

        SystemSetting::setValue(
            SystemSetting::KEY_ROOM_SUPPLY_IGNORE_WALLET_ELIGIBILITY_EXPIRES_AT,
            Carbon::now()->addHour()->toIso8601String(),
            'Ablaufzeitpunkt für Room-Supply-Testmodus.',
        );

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Room-Supply-Testmodus wurde für 60 Minuten aktiviert.');
    }

    public function disable(): RedirectResponse
    {
        SystemSetting::setValue(
            SystemSetting::KEY_ROOM_SUPPLY_IGNORE_WALLET_ELIGIBILITY_ENABLED,
            '0',
            'Erlaubt Room-Supply im Entwicklungsmodus ohne Wallet-Eligibility.',
        );

        SystemSetting::setValue(
            SystemSetting::KEY_ROOM_SUPPLY_IGNORE_WALLET_ELIGIBILITY_EXPIRES_AT,
            null,
            'Ablaufzeitpunkt für Room-Supply-Testmodus.',
        );

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Room-Supply-Testmodus wurde deaktiviert.');
    }
}
