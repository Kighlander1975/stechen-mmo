<?php

namespace App\Services;

use App\Models\RewardClaim;
use App\Models\User;
use App\Services\Phase3\Phase3LocalTestHarnessService;
use Throwable;

class RegistrationBonusBackfillService
{
    public function __construct(
        private readonly RewardService $rewardService,
        private readonly Phase3LocalTestHarnessService $phase3LocalTestHarness,
    ) {
    }

    /**
     * @return array{
     *     checked: int,
     *     eligible: int,
     *     granted: int,
     *     already_granted: int,
     *     excluded: int,
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
            'excluded' => 0,
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

                if ($this->isExcludedPhase3TestUser($user)) {
                    $summary['excluded']++;

                    continue;
                }

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

    /**
     * @return array{
     *     checked: int,
     *     eligible: int,
     *     granted: int,
     *     already_granted: int,
     *     email_unverified: int,
     *     excluded: int,
     *     failed: int,
     *     failures: array<int, string>
     * }
     */
    public function grantAllVerifiedOpenUsers(): array
    {
        $summary = [
            'checked' => 0,
            'eligible' => 0,
            'granted' => 0,
            'already_granted' => 0,
            'email_unverified' => 0,
            'excluded' => 0,
            'failed' => 0,
            'failures' => [],
        ];

        User::query()
            ->orderBy('id')
            ->chunkById(100, function ($users) use (&$summary): void {
                foreach ($users as $user) {
                    $summary['checked']++;

                    $result = $this->grantVerifiedUser($user);

                    match ($result['status']) {
                        'granted' => $summary['granted']++,
                        'already_granted' => $summary['already_granted']++,
                        'email_unverified' => $summary['email_unverified']++,
                        'excluded' => $summary['excluded']++,
                        default => $summary['failed']++,
                    };

                    if ($result['status'] === 'granted' || $result['status'] === 'already_granted') {
                        $summary['eligible']++;
                    }

                    if ($result['status'] === 'failed') {
                        $summary['failures'][$user->id] = $result['message'];
                    }
                }
            });

        return $summary;
    }

    /**
     * @return array{
     *     status: 'already_granted'|'email_unverified'|'excluded'|'granted'|'failed',
     *     message: string
     * }
     */
    public function grantVerifiedUser(User $user): array
    {
        if ($this->isExcludedPhase3TestUser($user)) {
            return [
                'status' => 'excluded',
                'message' => 'Phase-3-Testuser sind vom Startguthaben-Backfill ausgeschlossen.',
            ];
        }

        if ($this->hasRegistrationBonus($user)) {
            return [
                'status' => 'already_granted',
                'message' => 'Das Startguthaben war für diesen Account bereits eingerichtet.',
            ];
        }

        if (! $user->hasVerifiedEmail()) {
            return [
                'status' => 'email_unverified',
                'message' => 'Das Startguthaben kann erst nach bestätigter E-Mail-Adresse eingerichtet werden.',
            ];
        }

        try {
            $this->rewardService->grantRegistrationBonus($user);

            return [
                'status' => 'granted',
                'message' => 'Das Startguthaben wurde erfolgreich eingerichtet.',
            ];
        } catch (Throwable $throwable) {
            return [
                'status' => 'failed',
                'message' => 'Das Startguthaben konnte nicht eingerichtet werden: '.$throwable->getMessage(),
            ];
        }
    }

    public function hasRegistrationBonus(User $user): bool
    {
        return RewardClaim::query()
            ->where('idempotency_key', $this->rewardService->registrationBonusIdempotencyKey($user))
            ->exists();
    }

    private function isExcludedPhase3TestUser(User $user): bool
    {
        return $this->phase3LocalTestHarness->isPhase3TestUser($user);
    }
}

