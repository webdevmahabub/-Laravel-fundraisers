<?php

namespace App\Services;

use App\Models\OrganizerProfile;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Notifications\OrganizerApplicationApproved;
use App\Notifications\OrganizerApplicationRejected;
use App\Notifications\OrganizerApplicationSubmitted;
use HasinHayder\Tyro\Support\TyroAudit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrganizerApplicationService
{
    /**
     * Allowed MIME types for verification documents.
     *
     * @var array<string>
     */
    private const ALLOWED_DOCUMENT_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Maximum document file size in bytes (10 MB).
     */
    private const MAX_DOCUMENT_SIZE = 10 * 1024 * 1024;

    /**
     * Create a new organizer application for the given user.
     * A user can only have one application at a time.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function apply(User $user, array $data): OrganizerProfile
    {
        if ($user->organizerProfile !== null) {
            throw ValidationException::withMessages([
                'application' => 'You already have an organizer application.',
            ]);
        }

        return DB::transaction(function () use ($user, $data) {
            $profile = OrganizerProfile::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'national_id' => $data['national_id'] ?? null,
                'bio' => $data['bio'] ?? null,
                'website' => $data['website'] ?? null,
                'social_links' => $data['social_links'] ?? null,
            ]);

            TyroAudit::log('organizer.applied', $profile, null, [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            Notification::send($this->adminRecipients(), new OrganizerApplicationSubmitted($profile));

            return $profile;
        });
    }

    /**
     * Upload a verification document for an organizer profile.
     * Files are stored on the private 'local' disk and are never publicly accessible.
     *
     * @throws ValidationException
     */
    public function uploadDocument(
        OrganizerProfile $profile,
        UploadedFile $file,
        string $documentType
    ): VerificationDocument {
        $this->validateDocument($file);

        $path = $file->store(
            "verification-documents/{$profile->id}",
            'local'
        );

        $document = $profile->verificationDocuments()->create([
            'type' => $documentType,
            'disk' => 'local',
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'pending',
        ]);

        TyroAudit::log('organizer.document_uploaded', $profile, null, [
            'document_id' => $document->id,
            'type' => $documentType,
            'original_filename' => $file->getClientOriginalName(),
        ]);

        return $document;
    }

    /**
     * Delete a verification document and its stored file.
     * Only allowed while the application is still pending.
     *
     * @throws ValidationException
     */
    public function deleteDocument(OrganizerProfile $profile, VerificationDocument $document): void
    {
        if (! in_array($profile->status, ['pending', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'document' => 'Documents can only be removed while the application is pending or rejected.',
            ]);
        }

        if ($document->organizer_profile_id !== $profile->id) {
            throw ValidationException::withMessages([
                'document' => 'This document does not belong to your application.',
            ]);
        }

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        TyroAudit::log('organizer.document_deleted', $profile, null, [
            'document_id' => $document->id,
            'type' => $document->type,
        ]);
    }

    /**
     * Submit the application for admin review.
     * Moves status from pending/rejected → pending (re-submission).
     *
     * @throws ValidationException
     */
    public function submit(OrganizerProfile $profile): OrganizerProfile
    {
        if (! in_array($profile->status, ['pending', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'application' => 'This application cannot be submitted in its current state.',
            ]);
        }

        if ($profile->verificationDocuments()->count() === 0) {
            throw ValidationException::withMessages([
                'documents' => 'Please upload at least one verification document before submitting.',
            ]);
        }

        $oldStatus = $profile->status;
        $profile->status = 'pending';
        $profile->rejection_reason = null;
        $profile->save();

        TyroAudit::log('organizer.submitted', $profile, ['status' => $oldStatus], [
            'status' => 'pending',
        ]);

        return $profile;
    }

    /**
     * Approve an organizer application.
     *
     * @throws ValidationException
     */
    public function approve(OrganizerProfile $profile, User $reviewedBy): OrganizerProfile
    {
        if (! in_array($profile->status, ['pending', 'under_review'], true)) {
            throw ValidationException::withMessages([
                'application' => 'Only pending or under-review applications can be approved.',
            ]);
        }

        return DB::transaction(function () use ($profile, $reviewedBy) {
            $oldStatus = $profile->status;

            $profile->status = 'approved';
            $profile->verified_at = now();
            $profile->reviewed_by = $reviewedBy->id;
            $profile->rejection_reason = null;
            $profile->save();

            TyroAudit::log('organizer.approved', $profile, ['status' => $oldStatus], [
                'status' => 'approved',
                'reviewed_by' => $reviewedBy->id,
                'verified_at' => $profile->verified_at,
            ]);

            $profile->user->notify(new OrganizerApplicationApproved($profile));

            return $profile;
        });
    }

    /**
     * Reject an organizer application with a required reason.
     *
     * @throws ValidationException
     */
    public function reject(OrganizerProfile $profile, User $reviewedBy, string $reason): OrganizerProfile
    {
        if (! in_array($profile->status, ['pending', 'under_review'], true)) {
            throw ValidationException::withMessages([
                'application' => 'Only pending or under-review applications can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($profile, $reviewedBy, $reason) {
            $oldStatus = $profile->status;

            $profile->status = 'rejected';
            $profile->rejection_reason = $reason;
            $profile->reviewed_by = $reviewedBy->id;
            $profile->verified_at = null;
            $profile->save();

            TyroAudit::log('organizer.rejected', $profile, ['status' => $oldStatus], [
                'status' => 'rejected',
                'reviewed_by' => $reviewedBy->id,
                'rejection_reason' => $reason,
            ]);

            $profile->user->notify(new OrganizerApplicationRejected($profile, $reason));

            return $profile;
        });
    }

    /**
     * Mark an application as under review.
     *
     * @throws ValidationException
     */
    public function markUnderReview(OrganizerProfile $profile, User $reviewedBy): OrganizerProfile
    {
        if ($profile->status !== 'pending') {
            throw ValidationException::withMessages([
                'application' => 'Only pending applications can be marked as under review.',
            ]);
        }

        $oldStatus = $profile->status;
        $profile->status = 'under_review';
        $profile->reviewed_by = $reviewedBy->id;
        $profile->save();

        TyroAudit::log('organizer.under_review', $profile, ['status' => $oldStatus], [
            'status' => 'under_review',
            'reviewed_by' => $reviewedBy->id,
        ]);

        return $profile;
    }

    /**
     * Save admin notes on a profile without changing its status.
     */
    public function saveAdminNotes(OrganizerProfile $profile, ?string $notes): OrganizerProfile
    {
        $profile->admin_notes = $notes;
        $profile->save();

        return $profile;
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
     * Validate an uploaded document file.
     *
     * @throws ValidationException
     */
    private function validateDocument(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_DOCUMENT_SIZE) {
            throw ValidationException::withMessages([
                'document' => 'The document must not exceed 10 MB.',
            ]);
        }

        if (! in_array($file->getMimeType(), self::ALLOWED_DOCUMENT_MIMES, true)) {
            throw ValidationException::withMessages([
                'document' => 'Only PDF, JPEG, PNG, and WebP files are accepted.',
            ]);
        }
    }
}
