<?php

use App\Models\Campaign;
use App\Models\OrganizerProfile;
use App\Models\User;
use App\Notifications\CampaignApproved;
use App\Notifications\CampaignRejected;
use App\Notifications\CampaignSubmittedForReview;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

// ─── Helpers ────────────────────────────────────────────────────────────────

function approvedOrganizer(): array
{
    $user = User::factory()->create();
    $profile = OrganizerProfile::factory()->approved()->create(['user_id' => $user->id]);

    return [$user, $profile];
}

function adminUser(): User
{
    $admin = User::factory()->create();
    test()->giveRole($admin, 'admin');

    return $admin;
}

function campaignData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Help Rahim Medical Treatment',
        'short_description' => 'Rahim needs urgent surgery.',
        'story' => str_repeat('Full story of this campaign. ', 5),
        'goal_amount' => 5000,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(60)->toDateString(),
    ], $overrides);
}

// ─── Create ─────────────────────────────────────────────────────────────────

test('approved organizer can create a campaign draft', function () {
    [$user] = approvedOrganizer();

    $this->actingAs($user)
        ->post(route('organizer.campaigns.store'), campaignData())
        ->assertRedirect();

    expect(Campaign::where('title', 'Help Rahim Medical Treatment')->exists())->toBeTrue();
    expect(Campaign::first()->status)->toBe('draft');
});

test('non-approved user cannot create a campaign', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('organizer.campaigns.store'), campaignData())
        ->assertRedirect(route('organizer.application.show'));

    expect(Campaign::count())->toBe(0);
});

test('campaign slug is auto-generated and unique', function () {
    [$user] = approvedOrganizer();

    $this->actingAs($user)->post(route('organizer.campaigns.store'), campaignData());
    $this->actingAs($user)->post(route('organizer.campaigns.store'), campaignData());

    $slugs = Campaign::pluck('slug')->all();
    expect(count(array_unique($slugs)))->toBe(count($slugs));
});

test('campaign creation validates required fields', function () {
    [$user] = approvedOrganizer();

    $this->actingAs($user)
        ->post(route('organizer.campaigns.store'), [])
        ->assertSessionHasErrors(['title', 'short_description', 'story', 'goal_amount']);
});

// ─── Edit / Update ───────────────────────────────────────────────────────────

test('organizer can update their own draft campaign', function () {
    [$user, $profile] = approvedOrganizer();
    $campaign = Campaign::factory()->draft()->create(['organizer_profile_id' => $profile->id]);

    $this->actingAs($user)
        ->patch(route('organizer.campaigns.update', $campaign), campaignData(['title' => 'Updated Title']))
        ->assertRedirect(route('organizer.campaigns.show', $campaign));

    expect($campaign->fresh()->title)->toBe('Updated Title');
});

test('organizer cannot update another organizer\'s campaign (IDOR)', function () {
    [$owner, $ownerProfile] = approvedOrganizer();
    [$other] = approvedOrganizer();
    $campaign = Campaign::factory()->draft()->create(['organizer_profile_id' => $ownerProfile->id]);

    $this->actingAs($other)
        ->patch(route('organizer.campaigns.update', $campaign), campaignData(['title' => 'Hijacked']))
        ->assertStatus(403);

    expect($campaign->fresh()->title)->not->toBe('Hijacked');
});

test('organizer cannot edit an active campaign', function () {
    [$user, $profile] = approvedOrganizer();
    $campaign = Campaign::factory()->active()->create(['organizer_profile_id' => $profile->id]);

    $this->actingAs($user)
        ->patch(route('organizer.campaigns.update', $campaign), campaignData())
        ->assertStatus(403);
});

// ─── Submit ──────────────────────────────────────────────────────────────────

test('organizer can submit a draft campaign for review', function () {
    Notification::fake();
    $admin = adminUser();
    [$user, $profile] = approvedOrganizer();
    $campaign = Campaign::factory()->draft()->create(['organizer_profile_id' => $profile->id]);

    $this->actingAs($user)
        ->post(route('organizer.campaigns.submit', $campaign))
        ->assertRedirect(route('organizer.campaigns.show', $campaign));

    expect($campaign->fresh()->status)->toBe('pending');
    Notification::assertSentTo($admin, CampaignSubmittedForReview::class);
});

// ─── Admin moderation ────────────────────────────────────────────────────────

test('admin can approve a pending campaign', function () {
    Notification::fake();
    $admin = adminUser();
    [, $profile] = approvedOrganizer();
    $campaign = Campaign::factory()->pending()->create(['organizer_profile_id' => $profile->id]);

    $this->actingAs($admin)
        ->post(route('admin.campaigns.moderate', $campaign), ['action' => 'approve'])
        ->assertRedirect(route('admin.campaigns.show', $campaign));

    expect($campaign->fresh()->status)->toBe('active');
    expect($campaign->fresh()->is_verified)->toBeTrue();
    Notification::assertSentTo($profile->user, CampaignApproved::class);
});

test('admin can reject a campaign with a reason', function () {
    Notification::fake();
    $admin = adminUser();
    [, $profile] = approvedOrganizer();
    $campaign = Campaign::factory()->pending()->create(['organizer_profile_id' => $profile->id]);

    $this->actingAs($admin)
        ->post(route('admin.campaigns.moderate', $campaign), [
            'action' => 'reject',
            'rejection_reason' => 'Content violates our guidelines.',
        ])
        ->assertRedirect(route('admin.campaigns.show', $campaign));

    expect($campaign->fresh()->status)->toBe('rejected');
    Notification::assertSentTo($profile->user, CampaignRejected::class);
});

test('admin can suspend an active campaign', function () {
    $admin = adminUser();
    [, $profile] = approvedOrganizer();
    $campaign = Campaign::factory()->active()->create(['organizer_profile_id' => $profile->id]);

    $this->actingAs($admin)
        ->post(route('admin.campaigns.moderate', $campaign), ['action' => 'suspend'])
        ->assertRedirect(route('admin.campaigns.show', $campaign));

    expect($campaign->fresh()->status)->toBe('suspended');
});

test('admin can reactivate a suspended campaign', function () {
    $admin = adminUser();
    [, $profile] = approvedOrganizer();
    $campaign = Campaign::factory()->create([
        'organizer_profile_id' => $profile->id,
        'status' => 'suspended',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.campaigns.moderate', $campaign), ['action' => 'reactivate'])
        ->assertRedirect(route('admin.campaigns.show', $campaign));

    expect($campaign->fresh()->status)->toBe('active');
});

// ─── Cover image ─────────────────────────────────────────────────────────────

test('organizer can upload a cover image when creating a campaign', function () {
    Storage::fake('public');
    [$user, $profile] = approvedOrganizer();

    $this->actingAs($user)->post(
        route('organizer.campaigns.store'),
        campaignData(['cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 675)])
    );

    $campaign = Campaign::first();
    expect($campaign->cover_image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($campaign->cover_image_path);
});
