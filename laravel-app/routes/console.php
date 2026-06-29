<?php

use App\Models\GameRoom;
use App\Services\GameRooms\GameRoomStartCoordinatorService;
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


Artisan::command('game-rooms:advance-starts {--limit=100 : Maximum number of rooms per phase to evaluate} {--dry-run : Preview matching rooms without changing their status}', function (GameRoomStartCoordinatorService $startCoordinator) {
    $limit = (int) $this->option('limit');
    $dryRun = (bool) $this->option('dry-run');

    if ($limit < 1) {
        $this->error('The --limit option must be a positive integer.');

        return self::FAILURE;
    }

    $fullRooms = GameRoom::query()
        ->where('status', GameRoom::STATUS_FULL)
        ->orderBy('id')
        ->limit($limit)
        ->get();

    $startingRooms = GameRoom::query()
        ->where('status', GameRoom::STATUS_STARTING)
        ->whereNotNull('starts_at')
        ->where('starts_at', '<=', now())
        ->orderBy('starts_at')
        ->orderBy('id')
        ->limit($limit)
        ->get();

    $summary = [
        'full_evaluated' => $fullRooms->count(),
        'start_requested' => 0,
        'starting_due_evaluated' => $startingRooms->count(),
        'finalized' => 0,
        'dry_run' => $dryRun,
    ];

    if (! $dryRun) {
        foreach ($fullRooms as $room) {
            if ($startCoordinator->requestStartIfReady($room)) {
                $summary['start_requested']++;
            }
        }

        foreach ($startingRooms as $room) {
            if ($startCoordinator->finalizeStartIfDue($room)) {
                $summary['finalized']++;
            }
        }
    }

    $this->info($dryRun ? 'Game room starts dry-run completed.' : 'Game room starts advanced.');

    $this->table(
        ['Metric', 'Value'],
        [
            ['full_evaluated', $summary['full_evaluated']],
            ['start_requested', $summary['start_requested']],
            ['starting_due_evaluated', $summary['starting_due_evaluated']],
            ['finalized', $summary['finalized']],
            ['dry_run', $summary['dry_run'] ? 'yes' : 'no'],
        ],
    );

    return self::SUCCESS;
})->purpose('Advance game room start phases');

