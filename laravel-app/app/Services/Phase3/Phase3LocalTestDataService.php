<?php

namespace App\Services\Phase3;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Carbon;
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
}
