<?php

namespace App\Http\Controllers;

use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RewardController extends Controller
{
    public function claimDailyLogin(Request $request, RewardService $rewardService): RedirectResponse
    {
        $user = $request->user();

        $status = $rewardService->getDailyClaimStatus($user);

        if (! $status['eligible']) {
            return redirect()
                ->route('dashboard')
                ->with('warning', $this->dailyClaimWarningMessage($status['reason'] ?? null));
        }

        try {
            $claim = $rewardService->claimDailyLoginBonus($user);
        } catch (RuntimeException) {
            return redirect()
                ->route('dashboard')
                ->with('warning', 'Der tägliche Bonus ist aktuell nicht verfügbar.');
        }

        return redirect()
            ->route('dashboard')
            ->with('success', sprintf(
                'Täglicher Bonus abgeholt: %s St$ gutgeschrieben.',
                number_format($claim->amount_units, 0, ',', '.'),
            ));
    }

    private function dailyClaimWarningMessage(?string $reason): string
    {
        return match ($reason) {
            'email_not_verified' => 'Bitte bestätige zuerst deine E-Mail-Adresse.',
            'missing_play_money_wallet' => 'Für den täglichen Bonus muss zuerst dein Startguthaben eingerichtet sein.',
            'registration_reward_day' => 'Der tägliche Bonus ist erst ab dem nächsten Belohnungstag verfügbar.',
            'already_claimed' => 'Du hast den täglichen Bonus für den aktuellen Belohnungstag bereits abgeholt.',
            'no_active_reward_plan' => 'Aktuell ist kein täglicher Bonus verfügbar.',
            'missing_reward_plan_entry' => 'Für deinen aktuellen Streak ist kein Bonus hinterlegt.',
            default => 'Der tägliche Bonus ist aktuell nicht verfügbar.',
        };
    }
}
