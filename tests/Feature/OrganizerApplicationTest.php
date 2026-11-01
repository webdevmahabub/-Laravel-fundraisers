<?php

use App\Models\OrganizerProfile;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Notifications\OrganizerApplicationApproved;
use App\Notifications\OrganizerApplicationRejected;
use App\Notifications\OrganizerApplicationSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

// ─── Helpers ───────────────────────────────────────────────────────────────

function newUser(): User
{
    return User::factory()->create();
}

function newAdmin(): User
{
    $admin = User::factory()->create();
    test()->giveRole($admin, 'admin');

    return $admin;
}

function pendingProfile(User $user): OrganizerProfile
{
    return OrganizerProfile::factory()->pending()->create(['user_id' => $user->id]);
}

// ─── Application submission ─────────────────────────────────────────────────

test('authenticated user can submit an organizer application', function () {
    $user = newUser();

    $this->actingAs($user)
        ->post(route('organizer.application.store'), [
            'full_name' => 'Jane Doe',
            'phone' => '+1-555-0100',
            'bio' => 'I want to raise funds for a good cause.',
        ])
        ->assertRedirect(route('organizer.application.show'));

    expect(OrganizerProfile::where('user_id', $user->id)->exists())->toBeTrue();
});

test('guest cannot submit an organizer application', function () {
    $this->post(route('organizer.application.store'), ['full_name' => 'Jane'])->assertRedirect();

    expect(OrganizerProfile::count())->toBe(0);
});

test('user cannot submit a second application when one already exists', function () {
    $user = newUser();
    pendingProfile($user);

    // authorize() returns false → 403, not a redirect
    $this->actingAs($user)
        ->post(route('organizer.application.store'), ['full_name' => 'Jane Doe'])
        ->assertStatus(403);

    expect(OrganizerProfile::where('user_id', $user->id)->count())->toBe(1);
});

test('application notifies admins on submission', function () {
    Notification::fake();
    $admin = newAdmin();
    $user = newUser();

    $this->actingAs($user)->post(route('organizer.application.store'), [
        'full_name' => 'Jane Doe',
    ]);

    Notification::assertSentTo($admin, OrganizerApplicationSubmitted::class);
});

// ─── Document upload ────────────────────────────────────────────────────────

test('organizer can upload a verification document to a pending application', function () {
    Storage::fake('local');
    $user = newUser();
    $profile = pendingProfile($user);

    $this->actingAs($user)
        ->post(route('organizer.application.documents.upload'), [
            'type' => 'national_id',
            'document' => UploadedFile::fake()->create('id.pdf', 200, 'application/pdf'),
        ])
        ->assertRedirect(route('organizer.application.show'));

    expect($profile->verificationDocuments()->count())->toBe(1);
    $doc = $profile->verificationDocuments()->first();
    expect($doc->disk)->toBe('local');
    Storage::disk('local')->assertExists($doc->path);
});

test('document upload rejects files exceeding 10 MB', function () {
    Storage::fake('local');
    $user = newUser();
    pendingProfile($user);

    $this->actingAs($user)
        ->post(route('organizer.application.documents.upload'), [
            'type' => 'national_id',
            'document' => UploadedFile::fake()->create('big.pdf', 11_000, 'application/pdf'),
        ])
        ->assertSessionHasErrors('document');
});

test('user cannot delete a document belonging to another user profile (IDOR)', function () {
    Storage::fake('local');

    $owner = newUser();
    $other = newUser();
    $ownerProfile = pendingProfile($owner);

    $document = VerificationDocument::factory()->create([
        'organizer_profile_id' => $ownerProfile->id,
        'disk' => 'local',
        'path' => 'verification-documents/1/fake.pdf',
    ]);

    $this->actingAs($other)
        ->delete(route('organizer.application.documents.delete', $document))
        ->assertStatus(403);

    expect(VerificationDocument::find($document->id))->not->toBeNull();
});

// ─── Admin approve / reject ─────────────────────────────────────────────────

test('admin can approve a pending organizer application', function () {
    Notification::fake();
    $admin = newAdmin();
    $user = newUser();
    $profile = pendingProfile($user);

    $this->actingAs($admin)
        ->post(route('admin.organizer-verification.review', $profile), [
            'action' => 'approve',
        ])
        ->assertRedirect(route('admin.organizer-verification.index'));

    expect($profile->fresh()->status)->toBe('approved');
    expect($profile->fresh()->verified_at)->not->toBeNull();
    Notification::assertSentTo($user, OrganizerApplicationApproved::class);
});

test('admin can reject a pending organizer application with a reason', function () {
    Notification::fake();
    $admin = newAdmin();
    $user = newUser();
    $profile = pendingProfile($user);

    $this->actingAs($admin)
        ->post(route('admin.organizer-verification.review', $profile), [
            'action' => 'reject',
            'rejection_reason' => 'Insufficient documentation provided.',
        ])
        ->assertRedirect(route('admin.organizer-verification.index'));

    expect($profile->fresh()->status)->toBe('rejected');
    expect($profile->fresh()->rejection_reason)->toBe('Insufficient documentation provided.');
    Notification::assertSentTo($user, OrganizerApplicationRejected::class);
});

test('reject action requires a rejection reason', function () {
    $admin = newAdmin();
    $profile = pendingProfile(newUser());

    $this->actingAs($admin)
        ->post(route('admin.organizer-verification.review', $profile), [
            'action' => 'reject',
        ])
        ->assertSessionHasErrors('rejection_reason');
});

test('non-admin is redirected away from organizer verification page', function () {
    $user = newUser();
    $profile = pendingProfile(newUser());

    // Tyro admin middleware redirects non-admins rather than returning 403
    $this->actingAs($user)
        ->get(route('admin.organizer-verification.index'))
        ->assertRedirect();
});

test('admin can securely download a verification document', function () {
    Storage::fake('local');
    $admin = newAdmin();
    $profile = OrganizerProfile::factory()->pending()->create();
    $file = UploadedFile::fake()->create('id.pdf', 100, 'application/pdf');
    $path = $file->store("verification-documents/{$profile->id}", 'local');

    $document = VerificationDocument::factory()->create([
        'organizer_profile_id' => $profile->id,
        'disk' => 'local',
        'path' => $path,
        'mime_type' => 'application/pdf',
        'original_filename' => 'id.pdf',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.organizer-verification.documents.download', [$profile, $document]))
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'application/pdf');
});
