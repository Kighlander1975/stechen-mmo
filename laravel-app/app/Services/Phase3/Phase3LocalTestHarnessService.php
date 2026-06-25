<?php

namespace App\Services\Phase3;

use App\Models\SystemSetting;
use App\Models\User;

class Phase3LocalTestHarnessService
{
    public const TEST_USER_EMAIL_DOMAIN = 'phase3-test.stechen.local';

    public function isAvailableInCurrentEnvironment(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    public function isEnabled(): bool
    {
        return $this->isAvailableInCurrentEnvironment()
            && SystemSetting::phase3LocalTestHarnessIsEnabled();
    }

    public function enable(): void
    {
        if (! $this->isAvailableInCurrentEnvironment()) {
            return;
        }

        SystemSetting::setValue(
            SystemSetting::KEY_PHASE3_LOCAL_TEST_HARNESS_ENABLED,
            '1',
            'Aktiviert den lokalen Phase-3-Browser-Testmodus für Join-, Buy-in-, Leave-, Start- und Reset-Entwicklung.',
        );
    }

    public function disable(): void
    {
        SystemSetting::setValue(
            SystemSetting::KEY_PHASE3_LOCAL_TEST_HARNESS_ENABLED,
            '0',
            'Aktiviert den lokalen Phase-3-Browser-Testmodus für Join-, Buy-in-, Leave-, Start- und Reset-Entwicklung.',
        );
    }

    public function testUserEmailDomain(): string
    {
        return self::TEST_USER_EMAIL_DOMAIN;
    }

    public function isPhase3TestUser(User $user): bool
    {
        $email = trim(strtolower($user->email));

        return str_ends_with($email, '@'.self::TEST_USER_EMAIL_DOMAIN);
    }
}
