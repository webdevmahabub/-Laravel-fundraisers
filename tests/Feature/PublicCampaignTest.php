<?php

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\OrganizerProfile;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Str;

// ─── Helpers ────────────────────────────────────────────────────────────────

function publicCampaign(string $status = 'active'): Campaign
{
    $profile = OrganizerProfile::factory()->approved()->create();

    return Campaign::factory()->create([
        'organizer_profile_id' => $profile->id,
        'status' => $status,
        'title' => 'Public Test Campaign '.Str::random(6),
        'slug' => 'public-test-'.Str::random(8),
    ]);
}

// ─── Browse page ─────────────────────────────────────────────────────────────

test('public browse page returns 200', function () {
    $this->get(route('campaigns.index'))->assertStatus(200);
});

test('browse page can be filtered by category', function () {
    $category = CampaignCategory::factory()->create(['name' => 'Medical', 'slug' => 'medical-'.Str::random(4), 'is_active' => true]);
    $profile = OrganizerProfile::factory()->approved()->create();
    Campaign::factory()->active()->create([
        'organizer_profile_id' => $profile->id,
        'category_id' => $category->id,
        'title' => 'Medical Campaign',
    ]);

    $this->get(route('campaigns.index', ['category' => $category->slug]))
        ->assertStatus(200)
        ->assertSee('Medical Campaign');
});

test('browse page search returns matching campaigns', function () {
    $campaign = publicCampaign('active');

    $this->get(route('campaigns.index', ['search' => substr($campaign->title, 0, 10)]))
        ->assertStatus(200)
        ->assertSee($campaign->title);
});

// ─── Detail page ─────────────────────────────────────────────────────────────

test('public campaign detail page returns 200 for active campaign', function () {
    $campaign = publicCampaign('active');

    $this->get(route('campaigns.show', $campaign->slug))->assertStatus(200);
});

test('public campaign detail page shows campaign title', function () {
    $campaign = publicCampaign('active');

    $this->get(route('campaigns.show', $campaign->slug))
        ->assertSee($campaign->title);
});

test('draft campaign detail page returns 404 for guests', function () {
    $campaign = publicCampaign('draft');

    $this->get(route('campaigns.show', $campaign->slug))->assertStatus(404);
});

test('pending campaign detail page returns 404', function () {
    $campaign = publicCampaign('pending');

    $this->get(route('campaigns.show', $campaign->slug))->assertStatus(404);
});

test('non-existent campaign slug returns 404', function () {
    $this->get(route('campaigns.show', 'does-not-exist-ever'))->assertStatus(404);
});

test('completed campaign is publicly visible', function () {
    $campaign = publicCampaign('completed');

    $this->get(route('campaigns.show', $campaign->slug))->assertStatus(200);
});

// ─── Report form ─────────────────────────────────────────────────────────────

test('authenticated user can report an active campaign', function () {
    $campaign = publicCampaign('active');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('campaigns.report', $campaign->slug), [
            'reason' => 'fraud',
            'description' => 'This looks like a scam.',
        ])
        ->assertRedirect(route('campaigns.show', $campaign->slug));

    expect(Report::where('reporter_user_id', $user->id)->count())->toBe(1);
    expect(Report::first()->reason)->toBe('fraud');
});

test('guest cannot report a campaign (redirected to login)', function () {
    $campaign = publicCampaign('active');

    $this->post(route('campaigns.report', $campaign->slug), ['reason' => 'spam'])
        ->assertRedirect();

    expect(Report::count())->toBe(0);
});

test('duplicate reports from the same user are silently deduplicated', function () {
    $campaign = publicCampaign('active');
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('campaigns.report', $campaign->slug), ['reason' => 'spam']);
    $this->actingAs($user)->post(route('campaigns.report', $campaign->slug), ['reason' => 'fraud']);

    expect(Report::where('reporter_user_id', $user->id)->count())->toBe(1);
});

test('organizer cannot report their own campaign', function () {
    $profile = OrganizerProfile::factory()->approved()->create();
    $campaign = Campaign::factory()->active()->create(['organizer_profile_id' => $profile->id]);

    $this->actingAs($profile->user)
        ->post(route('campaigns.report', $campaign->slug), ['reason' => 'spam'])
        ->assertRedirect(route('campaigns.show', $campaign->slug))
        ->assertSessionHas('error');

    expect(Report::count())->toBe(0);
});

test('report reason must be from the allowed list', function () {
    $campaign = publicCampaign('active');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('campaigns.report', $campaign->slug), ['reason' => 'invented_reason'])
        ->assertSessionHasErrors('reason');
});
