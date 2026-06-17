<?php

namespace Tests\Feature\Auth;

use App\Models\LedgerEntry;
use App\Models\RewardClaim;
use App\Models\Wallet;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'legal_accepted' => 'on',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_users_receive_registration_bonus(): void
    {
        $this->post('/register', [
            'name' => 'Bonus User',
            'email' => 'bonus@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'legal_accepted' => 'on',
        ]);

        $user = auth()->user();

        $this->assertNotNull($user);

        $wallet = Wallet::where('user_id', $user->id)->firstOrFail();
        $claim = RewardClaim::where('user_id', $user->id)
            ->where('reward_type', RewardClaim::TYPE_REGISTRATION_BONUS)
            ->firstOrFail();

        $ledgerEntry = $claim->ledgerEntry;

        $this->assertSame(RewardService::REGISTRATION_BONUS_AMOUNT_UNITS, $wallet->balance_units);
        $this->assertSame(0, $wallet->reserved_units);

        $this->assertSame(RewardService::REGISTRATION_BONUS_AMOUNT_UNITS, $claim->amount_units);
        $this->assertSame(RewardClaim::STATUS_GRANTED, $claim->status);
        $this->assertSame('reward:registration_bonus:user:'.$user->id, $claim->idempotency_key);

        $this->assertTrue($ledgerEntry->user->is($user));
        $this->assertTrue($ledgerEntry->wallet->is($wallet));
        $this->assertSame(LedgerEntry::TYPE_GRANT, $ledgerEntry->entry_type);
        $this->assertSame(LedgerEntry::DIRECTION_CREDIT, $ledgerEntry->direction);
        $this->assertSame(RewardService::REGISTRATION_BONUS_AMOUNT_UNITS, $ledgerEntry->amount_units);
    }

    public function test_new_users_must_accept_legal_terms_to_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('legal_accepted');
        $this->assertGuest();

        $this->assertSame(0, RewardClaim::count());
        $this->assertSame(0, LedgerEntry::count());
        $this->assertSame(0, Wallet::count());
    }
}
