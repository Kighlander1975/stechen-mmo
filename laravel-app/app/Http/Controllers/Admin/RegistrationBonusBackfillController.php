<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewardClaim;
use App\Models\User;
use App\Services\RegistrationBonusBackfillService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class RegistrationBonusBackfillController extends Controller
{
    public function index(): View
    {
        $openUsers = User::query()
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

    public function storeForUser(User $user, RegistrationBonusBackfillService $backfillService): RedirectResponse
    {
        $result = $backfillService->grantVerifiedUser($user);

        $flashKey = match ($result['status']) {
            'granted' => 'status',
            'already_granted' => 'status',
            'email_unverified' => 'warning',
            default => 'error',
        };

        return redirect()
            ->route('admin.rewards.registration-bonus-backfill.index')
            ->with($flashKey, $result['message']);
    }
}
