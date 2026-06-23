<?php

namespace App\Services\GameRooms;

use App\Models\GameRoom;
use App\Models\SystemSetting;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameRoomSupplyService
{
    private const DEFAULT_RAKE_BASIS_POINTS = 200;

    /**
     * @return array{
     *     evaluated: int,
     *     eligible: int,
     *     created: int,
     *     skipped: int,
     *     override_requested: bool,
     *     override_used: bool,
     *     override_reason: string|null
     * }
     */
    public function supplySitAndGoRooms(bool $ignoreWalletEligibilityRequested = false): array
    {
        $overrideStatus = $this->resolveWalletEligibilityOverride($ignoreWalletEligibilityRequested);

        $summary = [
            'evaluated' => 0,
            'eligible' => 0,
            'created' => 0,
            'skipped' => 0,
            'override_requested' => $ignoreWalletEligibilityRequested,
            'override_used' => $overrideStatus['allowed'],
            'override_reason' => $overrideStatus['reason'],
        ];

        foreach ($this->candidateBuyIns() as $buyInUnits) {
            $eligibleWallets = $overrideStatus['allowed']
                ? GameRoom::MAX_ALLOWED_PLAYERS
                : $this->countEligibleWallets($buyInUnits);

            foreach ($this->candidatePlayerCounts() as $playerCount) {
                $summary['evaluated']++;

                if (! $overrideStatus['allowed'] && $eligibleWallets < $playerCount) {
                    $summary['skipped']++;
                    continue;
                }

                $summary['eligible']++;

                $openRooms = $this->countOpenRoomsForCombination($playerCount, $buyInUnits);

                if ($openRooms >= 1) {
                    continue;
                }

                GameRoom::create([
                    'public_code' => $this->generatePublicCode(),
                    'name' => $this->generateRoomName($playerCount, $buyInUnits),
                    'status' => GameRoom::STATUS_OPEN,
                    'asset_type' => Wallet::ASSET_PLAY_MONEY,
                    'currency_code' => Wallet::CURRENCY_STECHEN_DOLLAR,
                    'buy_in_units' => $buyInUnits,
                    'min_players' => $playerCount,
                    'max_players' => $playerCount,
                    'start_mode' => GameRoom::START_MODE_WHEN_FULL,
                    'scheduled_start_at' => null,
                    'rake_basis_points' => self::DEFAULT_RAKE_BASIS_POINTS,
                    'created_by_user_id' => null,
                ]);

                $summary['created']++;
            }
        }

        return $summary;
    }

    /**
     * @return list<int>
     */
    public function candidateBuyIns(): array
    {
        return [
            10,
            20,
            30,
            50,
            75,
            100,
            150,
            200,
            250,
            500,
            750,
            1000,
            1500,
            2000,
            3000,
            5000,
            7500,
            10000,
        ];
    }

    /**
     * @return list<int>
     */
    public function candidatePlayerCounts(): array
    {
        return range(GameRoom::MIN_ALLOWED_PLAYERS, GameRoom::MAX_ALLOWED_PLAYERS);
    }

    public function countEligibleWallets(int $buyInUnits): int
    {
        return Wallet::query()
            ->where('wallet_type', Wallet::TYPE_USER)
            ->where('asset_type', Wallet::ASSET_PLAY_MONEY)
            ->where('currency_code', Wallet::CURRENCY_STECHEN_DOLLAR)
            ->whereNotNull('user_id')
            ->whereRaw('(balance_units - reserved_units) >= ?', [$buyInUnits])
            ->count();
    }

    public function roomSupplyIgnoreWalletEligibilityIsCurrentlyAllowed(): bool
    {
        return $this->resolveWalletEligibilityOverride(true)['allowed'];
    }

    /**
     * @return array{allowed: bool, reason: string|null}
     */
    public function resolveWalletEligibilityOverride(bool $requested): array
    {
        if (! $requested) {
            return [
                'allowed' => false,
                'reason' => 'not_requested',
            ];
        }

        if (! app()->environment(['local', 'testing'])) {
            return [
                'allowed' => false,
                'reason' => 'environment_not_allowed',
            ];
        }

        if (! SystemSetting::roomSupplyIgnoreWalletEligibilityIsEnabled()) {
            return [
                'allowed' => false,
                'reason' => 'setting_disabled',
            ];
        }

        $expiresAtValue = SystemSetting::roomSupplyIgnoreWalletEligibilityExpiresAt();

        if ($expiresAtValue === null || trim($expiresAtValue) === '') {
            return [
                'allowed' => false,
                'reason' => 'missing_expiry',
            ];
        }

        try {
            $expiresAt = CarbonImmutable::parse($expiresAtValue);
        } catch (\Throwable) {
            return [
                'allowed' => false,
                'reason' => 'invalid_expiry',
            ];
        }

        if ($expiresAt->isPast()) {
            return [
                'allowed' => false,
                'reason' => 'expired',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    private function countOpenRoomsForCombination(int $playerCount, int $buyInUnits): int
    {
        return GameRoom::query()
            ->where('status', GameRoom::STATUS_OPEN)
            ->where('asset_type', Wallet::ASSET_PLAY_MONEY)
            ->where('currency_code', Wallet::CURRENCY_STECHEN_DOLLAR)
            ->where('buy_in_units', $buyInUnits)
            ->where('min_players', $playerCount)
            ->where('max_players', $playerCount)
            ->where('start_mode', GameRoom::START_MODE_WHEN_FULL)
            ->count();
    }

    private function generatePublicCode(): string
    {
        do {
            $publicCode = 'SNG-'.Str::upper((string) Str::ulid());
        } while (GameRoom::query()->where('public_code', $publicCode)->exists());

        return $publicCode;
    }

    private function generateRoomName(int $playerCount, int $buyInUnits): string
    {
        $capitals = [
            'Berlin',
            'Rom',
            'Wien',
            'Paris',
            'Madrid',
            'Lissabon',
            'Dublin',
            'London',
            'Oslo',
            'Stockholm',
            'Kopenhagen',
            'Helsinki',
            'Tallinn',
            'Riga',
            'Vilnius',
            'Warschau',
            'Prag',
            'Bratislava',
            'Budapest',
            'Ljubljana',
            'Zagreb',
            'Athen',
            'Sofia',
            'Bukarest',
            'Amsterdam',
            'Brüssel',
            'Luxemburg',
            'Bern',
            'Ottawa',
            'Tokio',
            'Seoul',
            'Canberra',
            'Wellington',
            'Reykjavik',
            'Ankara',
            'Kairo',
            'Nairobi',
            'Kapstadt',
            'Buenos Aires',
            'Santiago',
        ];

        $index = abs(crc32($playerCount.'-'.$buyInUnits)) % count($capitals);

        return $capitals[$index].' ('.$playerCount.')';
    }
}
