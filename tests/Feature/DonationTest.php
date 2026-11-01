<?php

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\FeeConfiguration;
use App\Models\OrganizerProfile;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\DonationReceived;
use App\Services\DonationService;
use Illuminate\Support\Facades\Notification;

// ─── Helpers ────────────────────────────────────────────────────────────────

function feesForDonation(): void
{
    FeeConfiguration::updateOrCreate(['key' => 'platform_fee_pct'], ['value' => 5.0,  'description' => 'test', 'is_active' => true]);
    FeeConfiguration::updateOrCreate(['key' => 'payment_processing_fee_pct'], ['value' => 2.9,  'description' => 'test', 'is_active' => true]);
    FeeConfiguration::updateOrCreate(['key' => 'payment_processing_fee_flat'], ['value' => 0.30, 'description' => 'test', 'is_active' => true]);
    FeeConfiguration::updateOrCreate(['key' => 'minimum_donation_amount'], ['value' => 1.0,  'description' => 'test', 'is_active' => true]);
}

function activeCampaign(): Campaign
{
    $profile = OrganizerProfile::factory()->approved()->create();

    return Campaign::factory()->active()->create([
        'organizer_profile_id' => $profile->id,
        'currency' => 'USD',
    ]);
}

function donationPaymentMethod(string $slug = 'bank-transfer-usd'): PaymentMethod
{
    return PaymentMethod::firstOrCreate(
        ['slug' => $slug],
        [
            'name' => 'Bank Transfer',
            'currency_type' => 'USD',
            'is_active' => true,
            'sort_order' => 1,
        ]
    );
}

function donationPayload(Campaign $campaign, array $overrides = []): array
{
    return array_merge([
        'gross_amount' => 100.00,
        'donor_name' => 'Alice',
        'donor_email' => 'alice@example.com',
        'payment_method' => donationPaymentMethod()->slug,
        'sender_mobile' => '01712345678',
        'transaction_id' => 'TRX-'.uniqid(),
        'currency' => $campaign->currency,
    ], $overrides);
}

// ─── Fee calculation correctness ─────────────────────────────────────────────

test('donation creates correct fee breakdown on record', function () {
    feesForDonation();
    $campaign = activeCampaign();

    // gross=100 → platform_fee=5.00, processing=100×0.029+0.30=3.20, net=91.80
    $this->post(route('campaigns.donate', $campaign->slug), donationPayload($campaign))
        ->assertRedirect(route('campaigns.show', $campaign->slug));

    $donation = Donation::first();
    expect($donation->status)->toBe('pending'); // manual transfer, pending until verified
    expect((float) $donation->gross_amount)->toBe(100.0);
    expect((float) $donation->platform_fee_amount)->toBe(5.0);
    expect((float) $donation->payment_processing_fee)->toBe(3.2);
    expect((float) $donation->net_amount)->toBe(91.8);
    expect((float) $donation->platform_fee_pct)->toBe(5.0);
});

// ─── Ledger entry ────────────────────────────────────────────────────────────

test('completed donation creates an immutable transaction ledger entry', function () {
    feesForDonation();
    $campaign = activeCampaign();

    $this->post(route('campaigns.donate', $campaign->slug), donationPayload($campaign));

    // Nothing is booked until an admin verified the actual transfer.
    expect(Transaction::count())->toBe(0);

    $admin = User::factory()->create();
    app(DonationService::class)->complete(Donation::first(), $admin);

    expect(Transaction::count())->toBe(1);
    $tx = Transaction::first();
    expect($tx->type)->toBe('donation');
    expect((float) $tx->amount)->toBe(100.0);
    expect($tx->transactionable_type)->toBe(Donation::class);
    // immutable — no updated_at column means it will always be null
    expect($tx->getRawOriginal('updated_at') ?? null)->toBeNull();
});

// ─── raised_amount update ────────────────────────────────────────────────────

test('completed donation increments campaign raised_amount', function () {
    feesForDonation();
    $campaign = activeCampaign();
    $before = (float) $campaign->raised_amount;

    $this->post(route('campaigns.donate', $campaign->slug), donationPayload($campaign, [
        'gross_amount' => 75.00,
    ]));

    // Still pending: the campaign total must not move yet.
    expect((float) $campaign->fresh()->raised_amount)->toBe($before);

    $admin = User::factory()->create();
    app(DonationService::class)->complete(Donation::first(), $admin);

    expect((float) $campaign->fresh()->raised_amount)->toBe($before + 75.0);
});

// ─── Anonymous donation ──────────────────────────────────────────────────────

test('anonymous donation hides donor name', function () {
    feesForDonation();
    $campaign = activeCampaign();

    $this->post(route('campaigns.donate', $campaign->slug), donationPayload($campaign, [
        'is_anonymous' => '1',
    ]));

    $donation = Donation::first();
    expect($donation->is_anonymous)->toBeTrue();
    expect($donation->resolvedDonorName())->toBe('Anonymous');
});

// ─── Manual transfer fields ──────────────────────────────────────────────────

test('manual payment without sender mobile and transaction id is rejected', function () {
    feesForDonation();
    $campaign = activeCampaign();

    $this->post(route('campaigns.donate', $campaign->slug), donationPayload($campaign, [
        'sender_mobile' => null,
        'transaction_id' => null,
    ]))->assertSessionHasErrors(['sender_mobile', 'transaction_id']);

    expect(Donation::count())->toBe(0);
});

test('a transaction id can only be used for one donation', function () {
    feesForDonation();
    $campaign = activeCampaign();

    $payload = donationPayload($campaign, ['transaction_id' => 'DUPTRX001']);
    $this->post(route('campaigns.donate', $campaign->slug), $payload);

    $this->post(route('campaigns.donate', $campaign->slug), $payload)
        ->assertSessionHasErrors('transaction_id');

    expect(Donation::count())->toBe(1);
});

// ─── Minimum amount enforcement ──────────────────────────────────────────────

test('donation below minimum amount is rejected', function () {
    feesForDonation();
    $campaign = activeCampaign();

    $this->post(route('campaigns.donate', $campaign->slug), donationPayload($campaign, [
        'gross_amount' => 0.50,
    ]))->assertSessionHasErrors('gross_amount');

    expect(Donation::count())->toBe(0);
});

// ─── Inactive campaign guard ──────────────────────────────────────────────────

test('donation route returns 404 for a non-active campaign slug', function () {
    feesForDonation();
    $profile = OrganizerProfile::factory()->approved()->create();
    $campaign = Campaign::factory()->pending()->create(['organizer_profile_id' => $profile->id]);

    $this->post(route('campaigns.donate', $campaign->slug), donationPayload($campaign))
        ->assertStatus(404);

    expect(Donation::count())->toBe(0);
});

// ─── Organizer notification ───────────────────────────────────────────────────

test('organizer is notified when a donation is verified', function () {
    Notification::fake();
    feesForDonation();
    $profile = OrganizerProfile::factory()->approved()->create();
    $campaign = Campaign::factory()->active()->create(['organizer_profile_id' => $profile->id]);

    $this->post(route('campaigns.donate', $campaign->slug), donationPayload($campaign, [
        'gross_amount' => 30.00,
    ]));

    // Pending donations never notify — the event fires on verification.
    Notification::assertNotSentTo($profile->user, DonationReceived::class);

    $admin = User::factory()->create();
    app(DonationService::class)->complete(Donation::first(), $admin);

    Notification::assertSentTo($profile->user, DonationReceived::class);
});

// ─── Fee snapshot immutability ───────────────────────────────────────────────

test('changing fee config after a donation does not alter the stored fee snapshot', function () {
    feesForDonation();
    $campaign = activeCampaign();

    $this->post(route('campaigns.donate', $campaign->slug), donationPayload($campaign));

    FeeConfiguration::where('key', 'platform_fee_pct')->update(['value' => 20.0]);

    $donation = Donation::first();
    expect((float) $donation->platform_fee_pct)->toBe(5.0);
    expect((float) $donation->platform_fee_amount)->toBe(5.0);
});

// ─── Authenticated donor linked correctly ────────────────────────────────────

test('authenticated donor is linked to the donation record', function () {
    feesForDonation();
    $campaign = activeCampaign();
    $donor = User::factory()->create();

    $this->actingAs($donor)->post(route('campaigns.donate', $campaign->slug), donationPayload($campaign, [
        'gross_amount' => 40.00,
        'donor_name' => $donor->name,
        'donor_email' => $donor->email,
    ]));

    $donation = Donation::first();
    expect($donation->user_id)->toBe($donor->id);

    // A signed-in donor never needs to type their own details again — the
    // validated request still requires name/email, so check the fallbacks.
    expect($donation->donor_name)->toBe($donor->name);
    expect($donation->donor_email)->toBe($donor->email);
});
