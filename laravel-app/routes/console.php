<?php

use App\Services\GameRooms\GameRoomSupplyService;
use App\Services\RegistrationBonusBackfillService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('rewards:backfill-registration-bonus {--dry-run : Preview eligible users without writing rewards} {--user-id= : Restrict backfill to a single user id}', function (RegistrationBonusBackfillService $backfillService) {
    $dryRun = (bool) $this->option('dry-run');
    $userIdOption = $this->option('user-id');
    $userId = $userIdOption === null ? null : (int) $userIdOption;

    if ($userId !== null && $userId <= 0) {
        $this->error('The --user-id option must be a positive integer.');

        return self::FAILURE;
    }

    $summary = $backfillService->run(
        dryRun: $dryRun,
        userId: $userId,
    );

    $this->info($dryRun ? 'Registration bonus backfill dry-run completed.' : 'Registration bonus backfill completed.');

    $this->table(
        ['Metric', 'Count'],
        [
            ['checked', $summary['checked']],
            ['eligible', $summary['eligible']],
            ['granted', $summary['granted']],
            ['already_granted', $summary['already_granted']],
            ['failed', $summary['failed']],
        ],
    );

    foreach ($summary['failures'] as $failedUserId => $message) {
        $this->error('User '.$failedUserId.': '.$message);
    }

    return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
})->purpose('Backfill registration bonus rewards for existing users');


Artisan::command('game-rooms:supply {--ignore-wallet-eligibility : Use local/testing admin test mode to create rooms without matching wallets}', function (GameRoomSupplyService $gameRoomSupplyService) {
    $summary = $gameRoomSupplyService->supplySitAndGoRooms(
        ignoreWalletEligibilityRequested: (bool) $this->option('ignore-wallet-eligibility'),
    );

    $this->info('Game room supply completed.');

    $this->table(
        ['Metric', 'Value'],
        [
            ['evaluated', $summary['evaluated']],
            ['eligible', $summary['eligible']],
            ['created', $summary['created']],
            ['skipped', $summary['skipped']],
            ['override_requested', $summary['override_requested'] ? 'yes' : 'no'],
            ['override_used', $summary['override_used'] ? 'yes' : 'no'],
            ['override_reason', $summary['override_reason'] ?? '-'],
        ],
    );

    return self::SUCCESS;
})->purpose('Supply open Sit\'n\'Go game rooms dynamically');
