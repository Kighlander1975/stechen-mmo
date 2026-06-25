<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\Phase3LocalTestHarnessController;
use App\Http\Controllers\Admin\RegistrationBonusBackfillController;
use App\Http\Controllers\Admin\RoomSupplyTestModeController;
use App\Http\Controllers\LobbyController;
use App\Http\Controllers\LobbyRoomsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RewardController;
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
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::post('/game-rooms/supply-test-mode/enable', [RoomSupplyTestModeController::class, 'enable'])
        ->name('game-rooms.supply-test-mode.enable');

    Route::post('/game-rooms/supply-test-mode/disable', [RoomSupplyTestModeController::class, 'disable'])
        ->name('game-rooms.supply-test-mode.disable');

    Route::post('/phase-3/local-test-harness/enable', [Phase3LocalTestHarnessController::class, 'enable'])
        ->name('phase3-local-test-harness.enable');

    Route::post('/phase-3/local-test-harness/disable', [Phase3LocalTestHarnessController::class, 'disable'])
        ->name('phase3-local-test-harness.disable');

    Route::post('/phase-3/local-test-harness/test-users/prepare', [Phase3LocalTestHarnessController::class, 'prepareTestUsers'])
        ->name('phase3-local-test-harness.prepare-test-users');

    Route::get('/rewards/registration-bonus-backfill', [RegistrationBonusBackfillController::class, 'index'])
        ->name('rewards.registration-bonus-backfill.index');

    Route::post('/rewards/registration-bonus-backfill', [RegistrationBonusBackfillController::class, 'store'])
        ->name('rewards.registration-bonus-backfill.store');

    Route::post('/rewards/registration-bonus-backfill/{user}', [RegistrationBonusBackfillController::class, 'storeForUser'])
        ->name('rewards.registration-bonus-backfill.user');
});

Route::get('/lobby', LobbyController::class)
    ->middleware(['auth', 'verified'])
    ->name('lobby');

Route::get('/lobby/rooms', LobbyRoomsController::class)
    ->middleware(['auth', 'verified'])
    ->name('lobby.rooms');

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
