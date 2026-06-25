<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewardClaim;
use App\Models\User;
use App\Services\Phase3\Phase3LocalTestHarnessService;
use App\Services\RegistrationBonusBackfillService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class RegistrationBonusBackfillController extends Controller
{
    public function index(Phase3LocalTestHarnessService $phase3LocalTestHarness): View
    {
        $openUsers = User::query()
            ->whereRaw('lower(email) not like ?', ['%@'.$phase3LocalTestHarness->testUserEmailDomain()])
            ->whereDoesntHave('rewardClaims', function (Builder $query): void {
                $query->where('reward_type', RewardClaim::TYPE_REGISTRATION_BONUS);
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $verifiedOpenUsers = $openUsers->filter(
            fn (User $user): bool => $user->hasVerifiedEmail()
        );

        $unverifiedOpenUsers = $openUsers->reject(
            fn (User $user): bool => $user->hasVerifiedEmail()
        );

        return view('admin.rewards.registration-bonus-backfill', [
            'openUsers' => $openUsers,
            'verifiedOpenUsersCount' => $verifiedOpenUsers->count(),
            'unverifiedOpenUsersCount' => $unverifiedOpenUsers->count(),
        ]);
    }

    public function store(RegistrationBonusBackfillService $backfillService): RedirectResponse
    {
        $summary = $backfillService->grantAllVerifiedOpenUsers();

        $message = sprintf(
            'Bulk-Backfill abgeschlossen: %d eingerichtet, %d bereits vorhanden, %d wegen offener E-Mail übersprungen, %d fehlgeschlagen.',
            $summary['granted'],
            $summary['already_granted'],
            $summary['email_unverified'],
            $summary['failed'],
        );

        $flashKey = $summary['failed'] > 0 ? 'warning' : 'status';

        return redirect()
            ->route('admin.rewards.registration-bonus-backfill.index')
            ->with($flashKey, $message);
    }

    public function storeForUser(User $user, RegistrationBonusBackfillService $backfillService): RedirectResponse
    {
        $result = $backfillService->grantVerifiedUser($user);

        $flashKey = match ($result['status']) {
            'granted' => 'status',
            'already_granted' => 'status',
            'email_unverified' => 'warning',
            'excluded' => 'warning',
            default => 'error',
        };

        return redirect()
            ->route('admin.rewards.registration-bonus-backfill.index')
            ->with($flashKey, $result['message']);
    }
}
