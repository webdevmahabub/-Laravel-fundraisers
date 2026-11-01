<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\OrganizerProfile;
use App\Models\User;
use App\Notifications\CampaignApproved;
use App\Notifications\CampaignRejected;
use App\Notifications\CampaignSubmittedForReview;
use HasinHayder\Tyro\Support\TyroAudit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CampaignService
{
    /**
     * Create a new campaign draft for an approved organizer.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(OrganizerProfile $organizerProfile, array $data): Campaign
    {
        $this->assertApproved($organizerProfile);

        return DB::transaction(function () use ($organizerProfile, $data) {
            $campaign = Campaign::create([
                'organizer_profile_id' => $organizerProfile->id,
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'slug' => $this->generateUniqueSlug($data['title']),
                'short_description' => $data['short_description'],
                'story' => $data['story'],
                'goal_amount' => $data['goal_amount'],
                'currency' => $data['currency'] ?? 'USD',
                'status' => 'draft',
                'is_verified' => false,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
            ]);

            if (! empty($data['cover_image']) && $data['cover_image'] instanceof UploadedFile) {
                $this->storeCoverImage($campaign, $data['cover_image']);
            }

            TyroAudit::log('campaign.created', $campaign, null, [
                'organizer_profile_id' => $organizerProfile->id,
                'title' => $campaign->title,
                'slug' => $campaign->slug,
            ]);

            return $campaign;
        });
    }

    /**
     * Update an existing campaign. Only allowed while draft or rejected.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(Campaign $campaign, array $data): Campaign
    {
        if (! in_array($campaign->status, ['draft', 'rejected', 'paused'], true)) {
            throw ValidationException::withMessages([
                'campaign' => 'This campaign cannot be edited in its current status.',
            ]);
        }

        return DB::transaction(function () use ($campaign, $data) {
            $campaign->fill([
                'category_id' => $data['category_id'] ?? $campaign->category_id,
                'title' => $data['title'],
                'short_description' => $data['short_description'],
                'story' => $data['story'],
                'goal_amount' => $data['goal_amount'],
                'currency' => $data['currency'] ?? $campaign->currency,
                'start_date' => $data['start_date'] ?? $campaign->start_date,
                'end_date' => $data['end_date'] ?? $campaign->end_date,
            ]);

            if (! empty($data['cover_image']) && $data['cover_image'] instanceof UploadedFile) {
                $this->storeCoverImage($campaign, $data['cover_image']);
            }

            $campaign->save();

            TyroAudit::log('campaign.updated', $campaign, null, [
                'title' => $campaign->title,
            ]);

            return $campaign;
        });
    }

    /**
     * Submit a draft campaign for admin review.
     *
     * @throws ValidationException
     */
    public function submit(Campaign $campaign, User $user): Campaign
    {
        if ($campaign->status !== 'draft') {
            throw ValidationException::withMessages([
                'campaign' => 'Only draft campaigns can be submitted for review.',
            ]);
        }

        $campaign->status = 'pending';
        $campaign->save();

        TyroAudit::log('campaign.submitted', $campaign, ['status' => 'draft'], [
            'status' => 'pending',
            'user_id' => $user->id,
        ]);

        Notification::send($this->adminRecipients(), new CampaignSubmittedForReview($campaign));

        return $campaign;
    }

    /**
     * Approve a campaign (admin action).
     *
     * @throws ValidationException
     */
    public function approve(Campaign $campaign, User $admin): Campaign
    {
        if (! in_array($campaign->status, ['pending'], true)) {
            throw ValidationException::withMessages([
                'campaign' => 'Only pending campaigns can be approved.',
            ]);
        }

        $oldStatus = $campaign->status;
        $campaign->status = 'active';
        $campaign->is_verified = true;
        $campaign->save();

        TyroAudit::log('campaign.approved', $campaign, ['status' => $oldStatus], [
            'status' => 'active',
            'admin_id' => $admin->id,
        ]);

        $campaign->organizerProfile->user->notify(new CampaignApproved($campaign));

        return $campaign;
    }

    /**
     * Reject a campaign (admin action).
     *
     * @throws ValidationException
     */
    public function reject(Campaign $campaign, User $admin, string $reason): Campaign
    {
        if (! in_array($campaign->status, ['pending', 'active'], true)) {
            throw ValidationException::withMessages([
                'campaign' => 'This campaign cannot be rejected in its current state.',
            ]);
        }

        $oldStatus = $campaign->status;
        $campaign->status = 'rejected';
        $campaign->is_verified = false;
        $campaign->save();

        TyroAudit::log('campaign.rejected', $campaign, ['status' => $oldStatus], [
            'status' => 'rejected',
            'reason' => $reason,
            'admin_id' => $admin->id,
        ]);

        $campaign->organizerProfile->user->notify(new CampaignRejected($campaign, $reason));

        return $campaign;
    }

    /**
     * Pause a campaign (admin or organizer action).
     *
     * @throws ValidationException
     */
    public function pause(Campaign $campaign, User $actor): Campaign
    {
        if ($campaign->status !== 'active') {
            throw ValidationException::withMessages([
                'campaign' => 'Only active campaigns can be paused.',
            ]);
        }

        $campaign->status = 'paused';
        $campaign->save();

        TyroAudit::log('campaign.paused', $campaign, ['status' => 'active'], [
            'status' => 'paused',
            'actor_id' => $actor->id,
        ]);

        return $campaign;
    }

    /**
     * Suspend a campaign (admin action) — halts it without deletion.
     *
     * @throws ValidationException
     */
    public function suspend(Campaign $campaign, User $actor): Campaign
    {
        if (! in_array($campaign->status, ['active', 'paused'], true)) {
            throw ValidationException::withMessages([
                'campaign' => 'Only active or paused campaigns can be suspended.',
            ]);
        }

        $oldStatus = $campaign->status;
        $campaign->status = 'suspended';
        $campaign->save();

        TyroAudit::log('campaign.suspended', $campaign, ['status' => $oldStatus], [
            'status' => 'suspended',
            'actor_id' => $actor->id,
        ]);

        return $campaign;
    }

    /**
     * Reactivate a suspended campaign back to active (admin action).
     *
     * @throws ValidationException
     */
    public function reactivate(Campaign $campaign, User $actor): Campaign
    {
        if ($campaign->status !== 'suspended') {
            throw ValidationException::withMessages([
                'campaign' => 'Only suspended campaigns can be reactivated.',
            ]);
        }

        $oldStatus = $campaign->status;
        $campaign->status = 'active';
        $campaign->save();

        TyroAudit::log('campaign.reactivated', $campaign, ['status' => $oldStatus], [
            'status' => 'active',
            'actor_id' => $actor->id,
        ]);

        return $campaign;
    }

    /**
     * All users holding an admin/super-admin role — used for review alerts.
     *
     * @return Collection<int, User>
     */
    protected function adminRecipients(): Collection
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['admin', 'super-admin']))->get();
    }

    /**
     * Store or replace the campaign cover image.
     * Images are stored on the public disk and served publicly.
     */
    public function storeCoverImage(Campaign $campaign, UploadedFile $file): Campaign
    {
        // Delete old cover image if present
        if ($campaign->cover_image_path) {
            Storage::disk('public')->delete($campaign->cover_image_path);
        }

        $path = $file->store("campaigns/{$campaign->id}/cover", 'public');
        $campaign->cover_image_path = $path;
        $campaign->save();

        return $campaign;
    }

    /**
     * Generate a URL-safe slug that is unique in the campaigns table.
     */
    public function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while (Campaign::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Assert that the organizer profile is approved before allowing campaign creation.
     *
     * @throws ValidationException
     */
    private function assertApproved(OrganizerProfile $profile): void
    {
        if (! $profile->isApproved()) {
            throw ValidationException::withMessages([
                'organizer' => 'You must be an approved organizer to create campaigns.',
            ]);
        }
    }
}
