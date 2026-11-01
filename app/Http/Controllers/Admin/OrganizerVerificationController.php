<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewOrganizerApplicationRequest;
use App\Models\OrganizerProfile;
use App\Models\VerificationDocument;
use App\Services\OrganizerApplicationService;
use HasinHayder\Tyro\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrganizerVerificationController extends Controller
{
    public function __construct(
        private readonly OrganizerApplicationService $applicationService
    ) {}

    /**
     * List all organizer applications, filterable by status.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $allowedStatuses = ['pending', 'under_review', 'approved', 'rejected', 'suspended', 'all'];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        $query = OrganizerProfile::with('user')
            ->orderBy('created_at', 'asc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $applications = $query->paginate(20)->withQueryString();

        $counts = [
            'pending' => OrganizerProfile::where('status', 'pending')->count(),
            'under_review' => OrganizerProfile::where('status', 'under_review')->count(),
            'approved' => OrganizerProfile::where('status', 'approved')->count(),
            'rejected' => OrganizerProfile::where('status', 'rejected')->count(),
        ];

        return view('admin.organizer-verification.index', compact('applications', 'status', 'counts'));
    }

    /**
     * Show a single application for review.
     */
    public function show(OrganizerProfile $organizerProfile): View
    {
        $organizerProfile->load(['user', 'verificationDocuments', 'reviewer']);

        $auditLogs = collect();
        if (config('tyro.audit.enabled', true) && class_exists(AuditLog::class)) {
            $auditLogs = AuditLog::with('user')
                ->where('auditable_type', OrganizerProfile::class)
                ->where('auditable_id', $organizerProfile->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('admin.organizer-verification.show', [
            'profile' => $organizerProfile,
            'auditLogs' => $auditLogs,
        ]);
    }

    /**
     * Process a review action (approve / reject / under_review).
     */
    public function review(ReviewOrganizerApplicationRequest $request, OrganizerProfile $organizerProfile): RedirectResponse
    {
        $action = $request->validated('action');
        $admin = $request->user();

        // Save any admin notes first regardless of action
        if ($request->filled('admin_notes')) {
            $this->applicationService->saveAdminNotes($organizerProfile, $request->validated('admin_notes'));
        }

        match ($action) {
            'approve' => $this->applicationService->approve($organizerProfile, $admin),
            'reject' => $this->applicationService->reject(
                $organizerProfile,
                $admin,
                $request->validated('rejection_reason')
            ),
            'under_review' => $this->applicationService->markUnderReview($organizerProfile, $admin),
        };

        $messages = [
            'approve' => 'Application approved. The organizer can now create campaigns.',
            'reject' => 'Application rejected.',
            'under_review' => 'Application marked as under review.',
        ];

        return redirect()->route('admin.organizer-verification.index')
            ->with('success', $messages[$action]);
    }

    /**
     * Serve a verification document as a secure download.
     * The file is read from the private local disk — it is never exposed via a public URL.
     */
    public function downloadDocument(OrganizerProfile $organizerProfile, VerificationDocument $document): Response
    {
        // Ensure the document belongs to this profile (IDOR guard)
        abort_unless($document->organizer_profile_id === $organizerProfile->id, 404);

        $storage = Storage::disk($document->disk);

        abort_unless($storage->exists($document->path), 404);

        return response($storage->get($document->path), 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($document->original_filename).'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
