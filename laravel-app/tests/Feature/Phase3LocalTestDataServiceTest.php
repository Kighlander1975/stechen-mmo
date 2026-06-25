<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Phase3\Phase3LocalTestDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase3LocalTestDataServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_users_returns_empty_list_when_harness_is_disabled(): void
    {
        $preparedUsers = app(Phase3LocalTestDataService::class)->prepareUsers();

        $this->assertSame([], $preparedUsers);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('wallets', 0);
        $this->assertDatabaseCount('ledger_entries', 0);
    }

    public function test_prepare_users_creates_deterministic_test_users_with_wallets(): void
    {
        SystemSetting::setValue(SystemSetting::KEY_PHASE3_LOCAL_TEST_HARNESS_ENABLED, '1');

        $preparedUsers = app(Phase3LocalTestDataService::class)->prepareUsers();

        $this->assertCount(6, $preparedUsers);
        $this->assertDatabaseCount('users', 6);
        $this->assertDatabaseCount('wallets', 6);
        $this->assertDatabaseCount('ledger_entries', 5);

        $player = User::query()->where('email', 'phase3.player1@phase3-test.stechen.local')->firstOrFail();
        $lowFunds = User::query()->where('email', 'phase3.lowfunds@phase3-test.stechen.local')->firstOrFail();
        $empty = User::query()->where('email', 'phase3.empty@phase3-test.stechen.local')->firstOrFail();

        $this->assertSame('Phase 3 Player 1', $player->name);
        $this->assertTrue(Hash::check('password', $player->password));
        $this->assertNotNull($player->email_verified_at);
        $this->assertSame(User::ACCOUNT_TYPE_PLAYER, $player->account_type);
        $this->assertSame(User::PLAYER_TIER_COMMON, $player->player_tier);
        $this->assertFalse((bool) $player->is_vip);
        $this->assertNull($player->staff_role);
        $this->assertTrue($player->hasPermission(User::PERMISSION_PLAY_GAME));
        $this->assertTrue($player->hasPermission(User::PERMISSION_ROOM_JOIN));

        $this->assertSame(10_000, $player->wallets()->firstOrFail()->balance_units);
        $this->assertSame(10, $lowFunds->wallets()->firstOrFail()->balance_units);
        $this->assertSame(0, $empty->wallets()->firstOrFail()->balance_units);

        $this->assertDatabaseHas('ledger_entries', [
            'user_id' => $player->id,
            'entry_type' => LedgerEntry::TYPE_ADJUSTMENT,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount_units' => 10_000,
            'balance_after_units' => 10_000,
            'reserved_after_units' => 0,
            'idempotency_key' => 'phase3-local-test-data:user:player1:balance:10000',
        ]);

        $this->assertDatabaseMissing('ledger_entries', [
            'user_id' => $empty->id,
            'entry_type' => LedgerEntry::TYPE_ADJUSTMENT,
        ]);
    }

    public function test_prepare_users_is_idempotent(): void
    {
        SystemSetting::setValue(SystemSetting::KEY_PHASE3_LOCAL_TEST_HARNESS_ENABLED, '1');

        $service = app(Phase3LocalTestDataService::class);

        $firstRun = $service->prepareUsers();
        $secondRun = $service->prepareUsers();

        $this->assertCount(6, $firstRun);
        $this->assertCount(6, $secondRun);
        $this->assertDatabaseCount('users', 6);
        $this->assertDatabaseCount('wallets', 6);
        $this->assertDatabaseCount('ledger_entries', 5);
    }

    public function test_prepare_users_adjusts_existing_test_user_wallet_to_target_balance(): void
    {
        SystemSetting::setValue(SystemSetting::KEY_PHASE3_LOCAL_TEST_HARNESS_ENABLED, '1');

        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'phase3.lowfunds@phase3-test.stechen.local',
            'permissions' => [],
        ]);

        Wallet::query()->create([
            'user_id' => $user->id,
            'wallet_type' => Wallet::TYPE_USER,
            'asset_type' => Wallet::ASSET_PLAY_MONEY,
            'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
            'balance_units' => 500,
            'reserved_units' => 0,
        ]);

        app(Phase3LocalTestDataService::class)->prepareUsers();

        $user->refresh();
        $wallet = $user->wallets()->firstOrFail();

        $this->assertSame('Phase 3 Low Funds', $user->name);
        $this->assertSame([User::PERMISSION_PLAY_GAME, User::PERMISSION_ROOM_JOIN], $user->permissions);
        $this->assertSame(10, $wallet->balance_units);

        $this->assertDatabaseHas('ledger_entries', [
            'user_id' => $user->id,
            'entry_type' => LedgerEntry::TYPE_ADJUSTMENT,
            'direction' => LedgerEntry::DIRECTION_DEBIT,
            'amount_units' => 490,
            'balance_after_units' => 10,
            'idempotency_key' => 'phase3-local-test-data:user:lowfunds:balance:10',
        ]);
    }
}
