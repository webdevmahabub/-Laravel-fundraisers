<?php

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\FeeConfiguration;
use App\Models\OrganizerProfile;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalCompleted;
use App\Notifications\WithdrawalFailed;
use App\Services\WithdrawalService;
use Illuminate\Support\Facades\Notification;

// ─── Helpers ────────────────────────────────────────────────────────────────

function withdrawalFees(): void
{
    FeeConfiguration::updateOrCreate(['key' => 'platform_fee_pct'], ['value' => 5.0,  'description' => 'test', 'is_active' => true]);
    FeeConfiguration::updateOrCreate(['key' => 'payment_processing_fee_pct'], ['value' => 2.9,  'description' => 'test', 'is_active' => true]);
    FeeConfiguration::updateOrCreate(['key' => 'payment_processing_fee_flat'], ['value' => 0.30, 'description' => 'test', 'is_active' => true]);
    FeeConfiguration::updateOrCreate(['key' => 'minimum_donation_amount'], ['value' => 1.0,  'description' => 'test', 'is_active' => true]);
    FeeConfiguration::updateOrCreate(['key' => 'withdrawal_fee_pct'], ['value' => 1.0,  'description' => 'test', 'is_active' => true]);
    FeeConfiguration::updateOrCreate(['key' => 'minimum_withdrawal_amount'], ['value' => 10.0, 'description' => 'test', 'is_active' => true]);
}

function organizerWithBalance(float $net = 200.0): array
{
    $organizer = User::factory()->create();
    $profile = OrganizerProfile::factory()->approved()->create(['user_id' => $organizer->id]);
    $campaign = Campaign::factory()->active()->create(['organizer_profile_id' => $profile->id]);

    Donation::create([
        'campaign_id' => $campaign->id,
        'donor_name' => 'TestDonor',
        'donor_email' => 'donor@test.com',
        'is_anonymous' => false,
        'gross_amount' => $net + 10,
        'platform_fee_pct' => 5.0,
        'platform_fee_amount' => 5.0,
        'payment_processing_fee' => 5.0,
        'net_amount' => $net,
        'currency' => 'USD',
        'status' => 'completed',
        'donated_at' => now(),
    ]);

    return [$organizer, $profile, $campaign];
}

// ─── Available balance ───────────────────────────────────────────────────────

test('available balance equals net donations minus completed withdrawals', function () {
    withdrawalFees();
    [, $profile] = organizerWithBalance(200.0);

    expect(app(WithdrawalService::class)->availableBalance($profile))->toBe(200.0);
});

test('available balance decreases after a completed withdrawal', function () {
    withdrawalFees();
    [, $profile] = organizerWithBalance(200.0);

    $admin = User::factory()->create();
    test()->giveRole($admin, 'admin');

    $service = app(WithdrawalService::class);
    $withdrawal = $service->request($profile, ['amount' => 100.0, 'currency' => 'USD', 'payment_method' => 'bank_transfer']);
    $service->complete($withdrawal, $admin);

    expect($service->availableBalance($profile))->toBe(100.0);
});

// ─── Request validation ──────────────────────────────────────────────────────

test('organizer cannot request a withdrawal exceeding available balance', function () {
    withdrawalFees();
    [$organizer] = organizerWithBalance(50.0);

    $this->actingAs($organizer)
        ->post(route('organizer.withdrawals.store'), ['amount' => 999.00, 'payment_method' => 'bank_transfer'])
        ->assertSessionHasErrors('amount');

    expect(Withdrawal::count())->toBe(0);
});

test('organizer cannot request below minimum withdrawal amount', function () {
    withdrawalFees();
    [$organizer] = organizerWithBalance(200.0);

    $this->actingAs($organizer)
        ->post(route('organizer.withdrawals.store'), ['amount' => 1.00, 'payment_method' => 'bank_transfer'])
        ->assertSessionHasErrors('amount');
});

test('non-organizer is redirected away from withdrawal store', function () {
    withdrawalFees();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('organizer.withdrawals.store'), ['amount' => 50.0, 'payment_method' => 'bank_transfer'])
        ->assertRedirect(route('organizer.application.show'));
});

// ─── Fee snapshot ────────────────────────────────────────────────────────────

test('withdrawal stores fee snapshot at time of request', function () {
    withdrawalFees();
    [, $profile] = organizerWithBalance(200.0);

    $withdrawal = app(WithdrawalService::class)->request($profile, [
        'amount' => 100.0,
        'currency' => 'USD',
        'payment_method' => 'bank_transfer',
    ]);

    expect((float) $withdrawal->withdrawal_fee_amount)->toBe(1.0);
    expect((float) $withdrawal->net_amount)->toBe(99.0);
});

// ─── Complete withdrawal ─────────────────────────────────────────────────────

test('admin completing a withdrawal creates a ledger transaction entry', function () {
    Notification::fake();
    withdrawalFees();
    [$organizer, $profile] = organizerWithBalance(200.0);

    $admin = User::factory()->create();
    test()->giveRole($admin, 'admin');

    $withdrawal = app(WithdrawalService::class)->request($profile, [
        'amount' => 100.0,
        'currency' => 'USD',
        'payment_method' => 'bank_transfer',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.withdrawals.complete', $withdrawal))
        ->assertRedirect(route('admin.withdrawals.show', $withdrawal));

    expect($withdrawal->fresh()->status)->toBe('completed');
    expect(Transaction::where('type', 'withdrawal')->count())->toBe(1);
    expect((float) Transaction::where('type', 'withdrawal')->first()->amount)->toBeLessThan(0);
    Notification::assertSentTo($organizer, WithdrawalCompleted::class);
});

// ─── Fail withdrawal ─────────────────────────────────────────────────────────

test('admin can mark a withdrawal as failed with a reason', function () {
    Notification::fake();
    withdrawalFees();
    [$organizer, $profile] = organizerWithBalance(200.0);

    $admin = User::factory()->create();
    test()->giveRole($admin, 'admin');

    $withdrawal = app(WithdrawalService::class)->request($profile, [
        'amount' => 100.0,
        'currency' => 'USD',
        'payment_method' => 'bank_transfer',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.withdrawals.fail', $withdrawal), [
            'failed_reason' => 'Invalid bank account details.',
        ])
        ->assertRedirect(route('admin.withdrawals.show', $withdrawal));

    expect($withdrawal->fresh()->status)->toBe('failed');
    expect($withdrawal->fresh()->failed_reason)->toBe('Invalid bank account details.');
    Notification::assertSentTo($organizer, WithdrawalFailed::class);
});

// ─── Cancel ──────────────────────────────────────────────────────────────────

test('organizer can cancel their own pending withdrawal', function () {
    withdrawalFees();
    [$organizer, $profile] = organizerWithBalance(200.0);

    $withdrawal = app(WithdrawalService::class)->request($profile, [
        'amount' => 50.0,
        'currency' => 'USD',
        'payment_method' => 'bank_transfer',
    ]);

    $this->actingAs($organizer)
        ->delete(route('organizer.withdrawals.cancel', $withdrawal))
        ->assertRedirect(route('organizer.withdrawals.index'));

    expect($withdrawal->fresh()->status)->toBe('rejected');
});

test('organizer cannot cancel another organizer\'s withdrawal (IDOR)', function () {
    withdrawalFees();
    [, $profileA] = organizerWithBalance(200.0);
    [$organizerB] = organizerWithBalance(200.0);

    $withdrawal = app(WithdrawalService::class)->request($profileA, [
        'amount' => 50.0,
        'currency' => 'USD',
        'payment_method' => 'bank_transfer',
    ]);

    $this->actingAs($organizerB)
        ->delete(route('organizer.withdrawals.cancel', $withdrawal))
        ->assertStatus(403);

    expect($withdrawal->fresh()->status)->toBe('pending');
});
