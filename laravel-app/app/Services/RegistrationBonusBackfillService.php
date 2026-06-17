<?php

namespace App\Services;

use App\Models\RewardClaim;
use App\Models\User;
use Throwable;

class RegistrationBonusBackfillService
{
    public function __construct(
        private readonly RewardService $rewardService,
    ) {
    }

    /**
     * @return array{
     *     checked: int,
     *     eligible: int,
     *     granted: int,
     *     already_granted: int,
     *     failed: int,
     *     failures: array<int, string>
     * }
     */
    public function run(bool $dryRun = true, ?int $userId = null): array
    {
        $summary = [
            'checked' => 0,
            'eligible' => 0,
            'granted' => 0,
            'already_granted' => 0,
            'failed' => 0,
            'failures' => [],
        ];

        $query = User::query()->orderBy('id');

        if ($userId !== null) {
            $query->whereKey($userId);
        }

        $query->chunkById(100, function ($users) use (&$summary, $dryRun): void {
            foreach ($users as $user) {
                $summary['checked']++;

                if ($this->hasRegistrationBonus($user)) {
                    $summary['already_granted']++;

                    continue;
                }

                $summary['eligible']++;

                if ($dryRun) {
                    continue;
                }

                try {
                    $this->rewardService->grantRegistrationBonus($user);
                    $summary['granted']++;
                } catch (Throwable $throwable) {
                    $summary['failed']++;
                    $summary['failures'][$user->id] = $throwable->getMessage();
                }
            }
        });

        return $summary;
    }

    private function hasRegistrationBonus(User $user): bool
    {
        return RewardClaim::query()
            ->where('idempotency_key', $this->rewardService->registrationBonusIdempotencyKey($user))
            ->exists();
    }
}

