<?php

use App\Http\Controllers\ProfileController;
use App\Models\Wallet;
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


Route::get('/admin', function () {
    return view('admin.dashboard');
})->middleware(['auth', 'permission:admin.access'])->name('admin.dashboard');

Route::get('/dashboard', function () {
    $playMoneyWallet = Wallet::query()
        ->where('user_id', request()->user()->id)
        ->where('wallet_type', Wallet::TYPE_USER)
        ->where('asset_type', Wallet::ASSET_PLAY_MONEY)
        ->where('currency_code', Wallet::CURRENCY_STECHEN_DOLLAR)
        ->first();

    return view('dashboard', [
        'playMoneyBalanceUnits' => $playMoneyWallet?->balance_units ?? 0,
    ]);
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


