<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizerApplicationRequest;
use App\Http\Requests\UpdateOrganizerApplicationRequest;
use App\Http\Requests\UploadVerificationDocumentRequest;
use App\Models\VerificationDocument;
use App\Services\OrganizerApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizerApplicationController extends Controller
{
    public function __construct(
        private readonly OrganizerApplicationService $applicationService
    ) {}

    /**
     * Show the application status page.
     * If the user has no application yet, show the "apply" prompt.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = $user->organizerProfile()->with('verificationDocuments')->first();

        return view('organizer.application.show', compact('profile'));
    }

    /**
     * Show the new application form.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->organizerProfile !== null) {
            return redirect()->route('organizer.application.show')
                ->with('info', 'You already have an organizer application.');
        }

        return view('organizer.application.create');
    }

    /**
     * Store a new organizer application.
     */
    public function store(StoreOrganizerApplicationRequest $request): RedirectResponse
    {
        $profile = $this->applicationService->apply(
            $request->user(),
            $request->validated()
        );

        return redirect()->route('organizer.application.show')
            ->with('success', 'Your application has been submitted. Please upload your verification documents.');
    }

    /**
     * Show the edit form for an existing application.
     */
    public function edit(Request $request): View|RedirectResponse
    {
        $profile = $request->user()->organizerProfile;

        if ($profile === null) {
            return redirect()->route('organizer.application.create');
        }

        $this->authorize('update', $profile);

        return view('organizer.application.edit', compact('profile'));
    }

    /**
     * Update the application details.
     */
    public function update(UpdateOrganizerApplicationRequest $request): RedirectResponse
    {
        $profile = $request->user()->organizerProfile;

        $this->authorize('update', $profile);

        $profile->update($request->validated());

        return redirect()->route('organizer.application.show')
            ->with('success', 'Your application details have been updated.');
    }

    /**
     * Upload a verification document.
     */
    public function uploadDocument(UploadVerificationDocumentRequest $request): RedirectResponse
    {
        $profile = $request->user()->organizerProfile;

        $this->applicationService->uploadDocument(
            $profile,
            $request->file('document'),
            $request->validated('type')
        );

        return redirect()->route('organizer.application.show')
            ->with('success', 'Document uploaded successfully.');
    }

    /**
     * Delete a verification document.
     */
    public function deleteDocument(Request $request, VerificationDocument $document): RedirectResponse
    {
        $profile = $request->user()->organizerProfile;

        // IDOR guard — document must belong to the authenticated user's profile
        abort_unless($profile && $document->organizer_profile_id === $profile->id, 403);

        $this->applicationService->deleteDocument($profile, $document);

        return redirect()->route('organizer.application.show')
            ->with('success', 'Document removed.');
    }
}
