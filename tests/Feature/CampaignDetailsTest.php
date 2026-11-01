<?php

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\CampaignImage;
use App\Models\Donation;
use App\Models\FeeConfiguration;
use App\Models\OrganizerProfile;
use App\Models\PaymentMethod;
use App\Models\Report;
use App\Models\User;

// ─── Helpers ────────────────────────────────────────────────────────────────

function detailCampaign(array $overrides = [], string $status = 'active'): Campaign
{
    $profile = OrganizerProfile::factory()->approved()->create();

    return Campaign::factory()->create([
        'organizer_profile_id' => $profile->id,
        'status' => $status,
        'currency' => 'BDT',
        'start_date' => now()->subDays(5),
        'end_date' => now()->addDays(30),
        ...$overrides,
    ]);
}

function feeConfig(): void
{
    FeeConfiguration::updateOrCreate(['key' => 'minimum_donation_amount'], ['value' => 1.0, 'description' => 'test', 'is_active' => true]);
    FeeConfiguration::updateOrCreate(['key' => 'platform_fee_pct'], ['value' => 5.0, 'description' => 'test', 'is_active' => true]);
    FeeConfiguration::updateOrCreate(['key' => 'payment_processing_fee_pct'], ['value' => 2.9, 'description' => 'test', 'is_active' => true]);
    FeeConfiguration::updateOrCreate(['key' => 'payment_processing_fee_flat'], ['value' => 0.30, 'description' => 'test', 'is_active' => true]);
}

function completedDonation(Campaign $campaign, array $overrides = []): Donation
{
    $donation = $campaign->donations()->create([
        'donor_name' => $overrides['donor_name'] ?? 'Sabrina Hossain',
        'donor_email' => $overrides['donor_email'] ?? 'sabrina@example.com',
        'is_anonymous' => $overrides['is_anonymous'] ?? false,
        'gross_amount' => $overrides['gross_amount'] ?? 500,
        'platform_fee_pct' => 5,
        'platform_fee_amount' => $overrides['platform_fee_amount'] ?? 25,
        'payment_processing_fee' => $overrides['payment_processing_fee'] ?? 14.80,
        'net_amount' => $overrides['net_amount'] ?? 460.20,
        'currency' => $campaign->currency,
        'payment_method' => 'bkash-bdt',
        'status' => 'completed',
        'message' => $overrides['message'] ?? null,
        'donated_at' => $overrides['donated_at'] ?? now()->subDay(),
    ]);

    // Mirror what DepositService::complete() does for a verified transfer.
    $campaign->increment('raised_amount', $donation->gross_amount);

    return $donation;
}

// ─── Details page renders correctly ─────────────────────────────────────────

test('campaign detail page shows title, goal, raised and status chip', function () {
    $campaign = detailCampaign();

    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertSee($campaign->title)
        ->assertSee(number_format((float) $campaign->goal_amount, 0))
        ->assertSee('Active');
});

test('campaign detail page shows created and last updated dates', function () {
    $campaign = detailCampaign();

    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertSee('Started')
        ->assertSee('Last updated');
});

test('campaign detail page shows images when present and a placeholder when not', function () {
    $campaign = detailCampaign();

    // No images: placeholder text appears, no gallery markup.
    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertSee('No photos have been added');

    $cover = 'campaigns-test/cover.png';
    Storage::disk('public')->put($cover, 'fake-binary-image');
    Storage::disk('public')->put('campaigns-test/gallery.png', 'fake-binary-image');

    $campaign->update(['cover_image_path' => $cover]);
    CampaignImage::create([
        'campaign_id' => $campaign->id,
        'path' => 'campaigns-test/gallery.png',
        'caption' => 'Distribution day',
        'sort_order' => 1,
    ]);

    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertSee('Distribution day');

    CampaignImage::query()->delete();
    Storage::disk('public')->delete('campaigns-test/gallery.png');
    Storage::disk('public')->delete($cover);
});

// ─── Fundraising progress ─────────────────────────────────────────────────────

test('fundraising progress reflects only completed donations', function () {
    $campaign = detailCampaign(['goal_amount' => 1000]);
    completedDonation($campaign, ['gross_amount' => 400]);
    completedDonation($campaign, ['gross_amount' => 100, 'donor_name' => 'Someone Else', 'donor_email' => 'other@example.com']);

    // A cancelled donation was never booked and must not appear anywhere.
    $campaign->donations()->create([
        'donor_name' => 'Ghost Donor',
        'gross_amount' => 999,
        'platform_fee_pct' => 5,
        'platform_fee_amount' => 49.95,
        'net_amount' => 899.05,
        'currency' => $campaign->currency,
        'status' => 'cancelled',
        'donated_at' => now(),
    ]);

    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertSee('৳500', false)
        ->assertSee('50%')
        ->assertSee('still needed')
        ->assertDontSee('Ghost Donor')
        ->assertDontSee('999');
});

test('anonymous donors display as anonymous', function () {
    $campaign = detailCampaign();
    completedDonation($campaign, ['is_anonymous' => true, 'donor_email' => 'secret@example.com']);

    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertSee('Anonymous', false)
        ->assertDontSee('secret@example.com');
});

// ─── Campaign status handling ─────────────────────────────────────────────────

// "donate-form-section" appears inside the page script for every campaign,
// so the closed-state tests assert against the id attribute instead.

test('expired campaign hides the donation form', function () {
    $campaign = detailCampaign(['end_date' => now()->subDay()]);

    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertDontSee('id="donate-form-section"', false)
        ->assertSee('no longer accepting donations');
});

test('paused campaign hides the donation form', function () {
    $campaign = detailCampaign(status: 'paused');

    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertDontSee('id="donate-form-section"', false)
        ->assertSee('Donations are paused');
});

test('fully funded campaign shows a funded state instead of the form', function () {
    $campaign = detailCampaign(['goal_amount' => 1000]);
    $campaign->update(['raised_amount' => 1000]);

    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertDontSee('id="donate-form-section"', false)
        ->assertSee('Fully funded');
});

test('closed campaign hides the donation form', function () {
    $campaign = detailCampaign(status: 'completed');

    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertDontSee('id="donate-form-section"', false);
});

test('expired and paused campaigns cannot receive donations through the API', function () {
    feeConfig();

    PaymentMethod::firstOrCreate(
        ['slug' => 'bkash-bdt'],
        ['name' => 'bKash', 'currency_type' => 'BDT', 'is_active' => true, 'sort_order' => 1]
    );

    $payload = [
        'gross_amount' => 100,
        'donor_name' => 'Frontend Trickster',
        'donor_email' => 'trick@example.com',
        'payment_method' => 'bkash-bdt',
        'sender_mobile' => '01712345678',
        'transaction_id' => 'TRX'.uniqid(),
        'currency' => 'BDT',
    ];

    // Expired: the slug still resolves (status is 'active') but the service
    // rejects the deadline server-side.
    $expired = detailCampaign(['end_date' => now()->subDay()]);

    $this->post(route('campaigns.donate', $expired->slug), $payload)
        ->assertSessionHasErrors('campaign');

    // Paused: the slug is already filtered out before the service runs.
    $paused = detailCampaign(status: 'paused');

    $this->post(route('campaigns.donate', $paused->slug), $payload)
        ->assertStatus(404);

    expect(Donation::count())->toBe(0);
});

// ─── Manual payment flow on the details page ──────────────────────────────────

test('manual payments stay pending verification after submitting the form', function () {
    feeConfig();
    $campaign = detailCampaign();

    PaymentMethod::firstOrCreate(
        ['slug' => 'bkash-bdt'],
        ['name' => 'bKash', 'currency_type' => 'BDT', 'is_active' => true, 'sort_order' => 1]
    );

    $this->post(route('campaigns.donate', $campaign->slug), [
        'gross_amount' => 2000,
        'donor_name' => 'Rashed Karim',
        'donor_email' => 'rashed@example.com',
        'payment_method' => 'bkash-bdt',
        'sender_mobile' => '01812345678',
        'transaction_id' => 'TRX88A2K19',
        'currency' => $campaign->currency,
    ])->assertRedirect(route('campaigns.show', $campaign->slug))
        ->assertSessionHas('success');

    $donation = Donation::first();
    expect($donation)->not->toBeNull();
    expect($donation->status)->toBe('pending'); // admin verification decides completion
    expect((float) $campaign->fresh()->raised_amount)->toBe(0.0); // total unchanged until verified
    expect($donation->gateway_transaction_id)->toBe('TRX88A2K19');
    expect($donation->sender_mobile)->toBe('01812345678');

    // A pending donation is never listed publicly.
    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertDontSee('TRX88A2K19')
        ->assertDontSee('Rashed Karim');
});

// ─── Payment methods API ──────────────────────────────────────────────────────

test('payment methods api hides disabled and gateway methods', function () {
    PaymentMethod::firstOrCreate(
        ['slug' => 'nagad-bdt'],
        ['name' => 'Nagad', 'currency_type' => 'BDT', 'is_active' => true, 'sort_order' => 2]
    );

    // A method the admin disabled must disappear from the public list.
    PaymentMethod::firstOrCreate(
        ['slug' => 'bkash-bdt'],
        ['name' => 'bKash', 'currency_type' => 'BDT', 'is_active' => false, 'sort_order' => 1]
    );

    // Gateway methods are hard-excluded until a real integration is wired up.
    PaymentMethod::firstOrCreate(
        ['slug' => 'stripe'],
        ['name' => 'Stripe', 'currency_type' => 'USD', 'is_active' => true, 'sort_order' => 1]
    );

    $slugs = collect($this->get('/api/payment-methods/BDT')->assertOk()->json())
        ->pluck('slug')
        ->all();

    expect(in_array('nagad-bdt', $slugs, true))->toBeTrue();
    expect(in_array('bkash-bdt', $slugs, true))->toBeFalse();  // disabled by admin
    expect(in_array('stripe', $slugs, true))->toBeFalse();     // no gateway integration yet
});

// ─── Related campaigns ────────────────────────────────────────────────────────

test('related campaigns exclude the current campaign and match the category', function () {
    $category = CampaignCategory::factory()->create();
    $profile = OrganizerProfile::factory()->approved()->create();
    $campaign = Campaign::factory()->active()->create([
        'organizer_profile_id' => $profile->id,
        'category_id' => $category->id,
        'currency' => 'BDT',
    ]);

    Campaign::factory()->active()->create(['category_id' => $category->id, 'organizer_profile_id' => $profile->id]);
    Campaign::factory()->active()->create(['category_id' => $category->id, 'organizer_profile_id' => $profile->id]);

    // A draft sibling must never appear on a public details page.
    Campaign::factory()->draft()->create(['category_id' => $category->id, 'organizer_profile_id' => $profile->id]);

    $response = $this->get(route('campaigns.show', $campaign->slug))->assertOk();

    foreach (Campaign::where('id', '!=', $campaign->id)->where('category_id', $category->id)->publiclyVisible()->get() as $related) {
        $response->assertSee($related->title);
    }

    $response->assertDontSee(Campaign::where('status', 'draft')->first()->title);
});

// ─── Reporting ────────────────────────────────────────────────────────────────

test('guest sees no report form, signed-in users can reach it', function () {
    $campaign = detailCampaign();

    $this->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertDontSee('report-form-section');
});

test('report form is available to authenticated non-organizer users', function () {
    $campaign = detailCampaign();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('campaigns.show', $campaign->slug))
        ->assertOk()
        ->assertSee('report-form-section')
        ->assertSee(route('campaigns.report', $campaign->slug));

    expect(Report::count())->toBe(0);
});
