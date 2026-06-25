<?php

namespace App\Services\Phase3;

use App\Models\GameRoom;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Phase3LocalTestDataService
{
    public const TEST_USER_PASSWORD = 'password';

    public function __construct(
        private readonly Phase3LocalTestHarnessService $phase3LocalTestHarness,
        private readonly WalletService $walletService,
    ) {
    }

    /**
     * @return array{
     *     cleanup: array<string, int>,
     *     users: array<int, array<string, mixed>>,
     *     rooms: array<int, array<string, mixed>>
     * }
     */
    public function activate(): array
    {
        return DB::transaction(function (): array {
            $cleanup = $this->cleanup();

            $this->phase3LocalTestHarness->enable();

            return [
                'cleanup' => $cleanup,
                'users' => $this->prepareUsers(),
                'rooms' => $this->prepareRooms(),
            ];
        });
    }

    /**
     * @return array<string, int>
     */
    public function deactivate(): array
    {
        return DB::transaction(function (): array {
            $cleanup = $this->cleanup();

            $this->phase3LocalTestHarness->disable();

            return $cleanup;
        });
    }

    /**
     * @return array<string, int>
     */
    public function cleanup(): array
    {
        $testUserIds = User::query()
            ->whereRaw('lower(email) like ?', ['%@'.$this->phase3LocalTestHarness->testUserEmailDomain()])
            ->pluck('id');

        $walletIds = Wallet::query()
            ->whereIn('user_id', $testUserIds)
            ->pluck('id');

        $deletedRooms = GameRoom::query()
            ->where('is_test', true)
            ->delete();

        $deletedLedgerEntries = LedgerEntry::query()
            ->where(function ($query) use ($walletIds, $testUserIds): void {
                $query
                    ->whereIn('wallet_id', $walletIds)
                    ->orWhereIn('related_wallet_id', $walletIds)
                    ->orWhereIn('user_id', $testUserIds);
            })
            ->delete();

        $deletedWallets = Wallet::query()
            ->whereIn('id', $walletIds)
            ->delete();

        $deletedUsers = User::query()
            ->whereIn('id', $testUserIds)
            ->delete();

        return [
            'rooms' => $deletedRooms,
            'ledger_entries' => $deletedLedgerEntries,
            'wallets' => $deletedWallets,
            'users' => $deletedUsers,
        ];
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     name: string,
     *     email: string,
     *     password: string,
     *     target_balance_units: int,
     *     permissions: array<int, string>,
     *     user_id: int,
     *     created: bool
     * }>
     */
    public function prepareUsers(): array
    {
        if (! $this->phase3LocalTestHarness->isEnabled()) {
            return [];
        }

        $preparedUsers = [];

        foreach ($this->testUserDefinitions() as $definition) {
            $user = User::query()
                ->where('email', $definition['email'])
                ->first();

            $created = false;

            if ($user === null) {
                $user = new User();
                $created = true;
            }

            $user->forceFill([
                'name' => $definition['name'],
                'email' => $definition['email'],
                'password' => Hash::make(self::TEST_USER_PASSWORD),
                'email_verified_at' => $user->email_verified_at ?? Carbon::now(),
                'account_type' => User::ACCOUNT_TYPE_PLAYER,
                'player_tier' => User::PLAYER_TIER_COMMON,
                'is_vip' => false,
                'staff_role' => null,
                'permissions' => $definition['permissions'],
            ])->save();

            $this->walletService->getOrCreatePlayMoneyWallet($user);

            $this->walletService->adjustPlayMoneyBalanceTo(
                user: $user,
                targetBalanceUnits: $definition['target_balance_units'],
                idempotencyKey: 'phase3-local-test-data:user:'.$definition['key'].':balance:'.$definition['target_balance_units'],
                description: 'Phase-3 local test user wallet preparation',
                metadata: [
                    'source' => 'phase3_local_test_data',
                    'test_user_key' => $definition['key'],
                ],
                referenceType: User::class,
                referenceId: $user->id,
            );

            $preparedUsers[] = [
                'key' => $definition['key'],
                'name' => $user->name,
                'email' => $user->email,
                'password' => self::TEST_USER_PASSWORD,
                'target_balance_units' => $definition['target_balance_units'],
                'permissions' => $definition['permissions'],
                'user_id' => $user->id,
                'created' => $created,
            ];
        }

        return $preparedUsers;
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     public_code: string,
     *     name: string,
     *     buy_in_units: int,
     *     min_players: int,
     *     max_players: int
     * }>
     */
    public function prepareRooms(): array
    {
        if (! $this->phase3LocalTestHarness->isEnabled()) {
            return [];
        }

        $preparedRooms = [];

        foreach ($this->testRoomDefinitions() as $definition) {
            $room = GameRoom::query()->create([
                'public_code' => $definition['public_code'],
                'name' => $definition['name'],
                'status' => GameRoom::STATUS_OPEN,
                'asset_type' => Wallet::ASSET_PLAY_MONEY,
                'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
                'buy_in_units' => $definition['buy_in_units'],
                'min_players' => $definition['min_players'],
                'max_players' => $definition['max_players'],
                'start_mode' => GameRoom::START_MODE_WHEN_FULL,
                'scheduled_start_at' => null,
                'rake_basis_points' => $definition['rake_basis_points'],
                'created_by_user_id' => null,
                'is_test' => true,
            ]);

            $preparedRooms[] = [
                'key' => $definition['key'],
                'public_code' => $room->public_code,
                'name' => $room->name,
                'buy_in_units' => $room->buy_in_units,
                'min_players' => $room->min_players,
                'max_players' => $room->max_players,
            ];
        }

        return $preparedRooms;
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     name: string,
     *     email: string,
     *     target_balance_units: int,
     *     permissions: array<int, string>
     * }>
     */
    public function testUserDefinitions(): array
    {
        $domain = $this->phase3LocalTestHarness->testUserEmailDomain();
        $permissions = [
            User::PERMISSION_PLAY_GAME,
            User::PERMISSION_ROOM_JOIN,
        ];

        return [
            [
                'key' => 'player1',
                'name' => 'Phase 3 Player 1',
                'email' => 'phase3.player1@'.$domain,
                'target_balance_units' => 10_000,
                'permissions' => $permissions,
            ],
            [
                'key' => 'player2',
                'name' => 'Phase 3 Player 2',
                'email' => 'phase3.player2@'.$domain,
                'target_balance_units' => 10_000,
                'permissions' => $permissions,
            ],
            [
                'key' => 'player3',
                'name' => 'Phase 3 Player 3',
                'email' => 'phase3.player3@'.$domain,
                'target_balance_units' => 10_000,
                'permissions' => $permissions,
            ],
            [
                'key' => 'player4',
                'name' => 'Phase 3 Player 4',
                'email' => 'phase3.player4@'.$domain,
                'target_balance_units' => 10_000,
                'permissions' => $permissions,
            ],
            [
                'key' => 'lowfunds',
                'name' => 'Phase 3 Low Funds',
                'email' => 'phase3.lowfunds@'.$domain,
                'target_balance_units' => 10,
                'permissions' => $permissions,
            ],
            [
                'key' => 'empty',
                'name' => 'Phase 3 Empty Wallet',
                'email' => 'phase3.empty@'.$domain,
                'target_balance_units' => 0,
                'permissions' => $permissions,
            ],
        ];
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     public_code: string,
     *     name: string,
     *     buy_in_units: int,
     *     min_players: int,
     *     max_players: int,
     *     rake_basis_points: int
     * }>
     */
    public function testRoomDefinitions(): array
    {
        return [
            [
                'key' => 'heads-up-10',
                'public_code' => 'P3TEST-HU-10',
                'name' => '[TEST] Heads Up 10',
                'buy_in_units' => 10,
                'min_players' => 2,
                'max_players' => 2,
                'rake_basis_points' => 0,
            ],
            [
                'key' => 'small-500',
                'public_code' => 'P3TEST-4P-500',
                'name' => '[TEST] Vierer Tisch 500',
                'buy_in_units' => 500,
                'min_players' => 2,
                'max_players' => 4,
                'rake_basis_points' => 200,
            ],
            [
                'key' => 'small-2000',
                'public_code' => 'P3TEST-4P-2000',
                'name' => '[TEST] Vierer Tisch 2.000',
                'buy_in_units' => 2_000,
                'min_players' => 2,
                'max_players' => 4,
                'rake_basis_points' => 200,
            ],
            [
                'key' => 'large-10000',
                'public_code' => 'P3TEST-6P-10000',
                'name' => '[TEST] Sechser Tisch 10.000',
                'buy_in_units' => 10_000,
                'min_players' => 2,
                'max_players' => 6,
                'rake_basis_points' => 250,
            ],
        ];
    }
}
