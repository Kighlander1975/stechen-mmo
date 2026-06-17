<?php

use App\Http\Controllers\Admin\RegistrationBonusBackfillController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RewardController;
use App\Models\GameRoom;
use App\Models\Wallet;
use App\Services\RewardService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/rules', function () {
    return view('rules');
});

Route::view('/terms', 'legal.terms')->name('terms');

Route::view('/privacy', 'legal.privacy')->name('privacy');

Route::get('/vue-test', function () {
    return view('vue-test');
});

Route::get('/api/app-status', function () {
    return response()->json([
        'app' => config('app.name', 'Stechen-MMO'),
        'status' => 'ok',
        'environment' => app()->environment(),
        'version' => 'auth-foundation',
    ]);
});


Route::middleware(['auth', 'permission:admin.access'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/rewards/registration-bonus-backfill', [RegistrationBonusBackfillController::class, 'index'])
        ->name('rewards.registration-bonus-backfill.index');

    Route::post('/rewards/registration-bonus-backfill', [RegistrationBonusBackfillController::class, 'store'])
        ->name('rewards.registration-bonus-backfill.store');

    Route::post('/rewards/registration-bonus-backfill/{user}', [RegistrationBonusBackfillController::class, 'storeForUser'])
        ->name('rewards.registration-bonus-backfill.user');
});

Route::get('/lobby', function () {
    $status = request()->query('status');
    $startMode = request()->query('start_mode');
    $buyIn = request()->query('buy_in');
    $players = request()->query('players');

    $roomsQuery = GameRoom::query()
        ->withCount('activePlayers')
        ->whereIn('status', [
            GameRoom::STATUS_OPEN,
            GameRoom::STATUS_FULL,
            GameRoom::STATUS_RUNNING,
            GameRoom::STATUS_FINISHED,
        ]);

    if (in_array($status, [
        GameRoom::STATUS_OPEN,
        GameRoom::STATUS_FULL,
        GameRoom::STATUS_RUNNING,
        GameRoom::STATUS_FINISHED,
    ], true)) {
        $roomsQuery->where('status', $status);
    }

    if (in_array($startMode, [
        GameRoom::START_MODE_WHEN_FULL,
        GameRoom::START_MODE_SCHEDULED,
    ], true)) {
        $roomsQuery->where('start_mode', $startMode);
    }

    match ($buyIn) {
        'free' => $roomsQuery->where('buy_in_units', 0),
        'micro' => $roomsQuery->whereBetween('buy_in_units', [1, 500]),
        'low' => $roomsQuery->whereBetween('buy_in_units', [501, 2_000]),
        'medium' => $roomsQuery->whereBetween('buy_in_units', [2_001, 10_000]),
        'high' => $roomsQuery->where('buy_in_units', '>', 10_000),
        default => null,
    };

    match ($players) {
        'heads_up' => $roomsQuery->where('max_players', 2),
        'small' => $roomsQuery->whereBetween('max_players', [3, 4]),
        'medium' => $roomsQuery->whereBetween('max_players', [5, 6]),
        'large' => $roomsQuery->whereBetween('max_players', [7, GameRoom::MAX_ALLOWED_PLAYERS]),
        default => null,
    };

    $gameRooms = $roomsQuery
        ->orderByRaw("CASE status WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END", [
            GameRoom::STATUS_OPEN,
            GameRoom::STATUS_FULL,
            GameRoom::STATUS_RUNNING,
        ])
        ->orderBy('buy_in_units')
        ->orderBy('max_players')
        ->orderBy('name')
        ->get();

    return view('lobby.index', [
        'gameRooms' => $gameRooms,
        'filters' => [
            'status' => $status,
            'start_mode' => $startMode,
            'buy_in' => $buyIn,
            'players' => $players,
        ],
    ]);
})->middleware(['auth', 'verified'])->name('lobby');

Route::get('/dashboard', function (RewardService $rewardService) {
    $playMoneyWallet = Wallet::query()
        ->where('user_id', request()->user()->id)
        ->where('wallet_type', Wallet::TYPE_USER)
        ->where('asset_type', Wallet::ASSET_PLAY_MONEY)
        ->where('currency_code', Wallet::CURRENCY_STECHEN_DOLLAR)
        ->first();

    return view('dashboard', [
        'playMoneyBalanceUnits' => $playMoneyWallet?->balance_units ?? 0,
        'dailyClaimStatus' => $rewardService->getDailyClaimStatus(request()->user()),
    ]);
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/rewards/daily-login/claim', [RewardController::class, 'claimDailyLogin'])->name('rewards.daily-login.claim');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
