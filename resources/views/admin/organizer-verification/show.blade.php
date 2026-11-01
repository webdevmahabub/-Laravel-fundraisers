@extends('tyro-dashboard::layouts.admin')

@section('title', 'Review Application — ' . $profile->full_name)

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('admin.organizer-verification.index') }}">Organizer Verification</a>
<span class="breadcrumb-separator">/</span>
<span>{{ $profile->full_name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">{{ $profile->full_name }}</h1>
            <p class="page-description">Review the organizer application and submitted documents.</p>
        </div>
        <a href="{{ route('admin.organizer-verification.index') }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to List
        </a>
    </div>
</div>

<div class="grid-2">

    {{-- Left column: applicant info + documents --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">

        {{-- Status badge --}}
        <div class="card">
            <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    @php
                        $statusBadge = [
                            'pending'      => 'badge-warning',
                            'under_review' => 'badge-primary',
                            'approved'     => 'badge-success',
                            'rejected'     => 'badge-danger',
                            'suspended'    => 'badge-danger',
                        ][$profile->status] ?? 'badge-secondary';
                    @endphp
                    <span style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Current Status</span>
                    <div style="margin-top: 0.375rem;">
                        <span class="badge {{ $statusBadge }}" style="font-size: 0.95rem; padding: 0.35rem 0.9rem;">
                            {{ str_replace('_', ' ', ucfirst($profile->status)) }}
                        </span>
                    </div>
                </div>
                <div style="font-size: 0.875rem; color: var(--muted-foreground); text-align: right;">
                    Submitted {{ $profile->created_at->format('M d, Y') }}<br>
                    @if($profile->reviewer)
                        Reviewed by {{ $profile->reviewer->name }}
                    @endif
                </div>
            </div>
        </div>

        {{-- Applicant details --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Applicant Information</h3>
            </div>
            <div class="card-body">
                <dl style="display: grid; gap: 1rem;">
                    <div class="grid-2" style="gap: 1rem;">
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Account Name</dt>
                            <dd style="margin-top: 0.25rem; font-weight: 500;">{{ $profile->user->name }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Email</dt>
                            <dd style="margin-top: 0.25rem;">{{ $profile->user->email }}</dd>
                        </div>
                    </div>
                    <div class="grid-2" style="gap: 1rem;">
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Full Name</dt>
                            <dd style="margin-top: 0.25rem; font-weight: 500;">{{ $profile->full_name }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Phone</dt>
                            <dd style="margin-top: 0.25rem;">{{ $profile->phone ?? '—' }}</dd>
                        </div>
                    </div>
                    <div>
                        <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">National ID</dt>
                        <dd style="margin-top: 0.25rem; font-family: monospace;">{{ $profile->national_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Address</dt>
                        <dd style="margin-top: 0.25rem;">{{ $profile->address ?? '—' }}</dd>
                    </div>
                    @if($profile->bio)
                    <div>
                        <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Bio</dt>
                        <dd style="margin-top: 0.25rem; color: var(--muted-foreground);">{{ $profile->bio }}</dd>
                    </div>
                    @endif
                    @if($profile->website)
                    <div>
                        <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Website</dt>
                        <dd style="margin-top: 0.25rem;"><a href="{{ $profile->website }}" target="_blank" rel="noopener noreferrer">{{ $profile->website }}</a></dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Verification documents --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Verification Documents</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                @if($profile->verificationDocuments->isEmpty())
                    <div class="empty-state" style="padding: 2rem;">
                        <p class="empty-state-description">No documents uploaded.</p>
                    </div>
                @else
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>File</th>
                                    <th>Size</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($profile->verificationDocuments as $doc)
                                <tr>
                                    <td><span class="badge badge-secondary">{{ str_replace('_', ' ', ucfirst($doc->type)) }}</span></td>
                                    <td style="font-size: 0.875rem;">{{ $doc->original_filename }}</td>
                                    <td style="font-size: 0.875rem; color: var(--muted-foreground);">{{ number_format($doc->size / 1024, 1) }} KB</td>
                                    <td style="text-align: right;">
                                        <a href="{{ route('admin.organizer-verification.documents.download', [$profile, $doc]) }}"
                                            target="_blank"
                                            class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.75rem;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        @if($profile->rejection_reason)
        <div class="card" style="border-color: var(--destructive);">
            <div class="card-header">
                <h3 class="card-title" style="color: var(--destructive);">Previous Rejection Reason</h3>
            </div>
            <div class="card-body" style="color: var(--muted-foreground);">{{ $profile->rejection_reason }}</div>
        </div>
        @endif
    </div>

    {{-- Right column: review action form + admin notes --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">

        @if(in_array($profile->status, ['pending', 'under_review']))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Review Decision</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.organizer-verification.review', $profile) }}" method="POST" id="review-form">
                    @csrf
                    <input type="hidden" name="action" id="review-action" value="">

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="admin_notes" class="form-label">Admin Notes <span class="form-label-optional">(internal only)</span></label>
                        <textarea name="admin_notes" id="admin_notes" class="form-textarea" rows="3"
                            placeholder="Internal notes about this application...">{{ old('admin_notes', $profile->admin_notes) }}</textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;" id="rejection-reason-group" style="display: none;">
                        <label for="rejection_reason" class="form-label">
                            Rejection Reason <span style="color: var(--destructive);">*</span>
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-textarea @error('rejection_reason') is-invalid @enderror"
                            rows="3" placeholder="Explain why this application is being rejected...">{{ old('rejection_reason') }}</textarea>
                        @error('rejection_reason')
                            <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                        @enderror
                    </div>

                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <button type="button" class="btn btn-primary" onclick="submitReview('approve')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Approve
                        </button>
                        @if($profile->status === 'pending')
                        <button type="button" class="btn btn-secondary" onclick="submitReview('under_review')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Mark Under Review
                        </button>
                        @endif
                        <button type="button" class="btn btn-destructive" onclick="showRejectForm()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Admin notes (read-only if not actionable) --}}
        @if(!in_array($profile->status, ['pending', 'under_review']) && $profile->admin_notes)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Admin Notes</h3>
            </div>
            <div class="card-body" style="color: var(--muted-foreground);">{{ $profile->admin_notes }}</div>
        </div>
        @endif

        {{-- Application timeline --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Timeline</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--muted-foreground);">Application submitted</span>
                        <span>{{ $profile->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    @if($profile->verified_at)
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--muted-foreground);">Approved</span>
                        <span>{{ $profile->verified_at->format('M d, Y H:i') }}</span>
                    </div>
                    @endif
                    @if($profile->updated_at && $profile->updated_at->ne($profile->created_at))
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--muted-foreground);">Last updated</span>
                        <span>{{ $profile->updated_at->format('M d, Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Audit log for this application --}}
        @if(config('tyro.audit.enabled', true) && $auditLogs->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Audit Log</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($auditLogs as $log)
                            <tr>
                                <td>
                                    <span class="badge badge-secondary" style="font-family: monospace; font-size: 0.75rem;">
                                        {{ $log->event }}
                                    </span>
                                </td>
                                <td style="font-size: 0.875rem;">
                                    {{ $log->user?->name ?? 'System' }}
                                </td>
                                <td style="font-size: 0.875rem; color: var(--muted-foreground);">
                                    {{ $log->created_at->format('M d, Y H:i') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

    </div>

</div>

@push('scripts')
<script>
    const rejectionGroup = document.getElementById('rejection-reason-group');

    function showRejectForm() {
        if (rejectionGroup) {
            rejectionGroup.style.display = 'block';
            document.getElementById('rejection_reason').focus();
        }
        document.getElementById('review-action').value = 'reject';
    }

    function submitReview(action) {
        if (action === 'reject') {
            showRejectForm();
            return;
        }

        const confirmMessages = {
            approve: ['Approve Application', 'Are you sure you want to approve this organizer application? The applicant will be able to create campaigns.'],
            under_review: ['Mark Under Review', 'Mark this application as under review?'],
        };

        const [title, msg] = confirmMessages[action] || ['Confirm', 'Are you sure?'];
        showConfirm(title, msg).then(confirmed => {
            if (confirmed) {
                document.getElementById('review-action').value = action;
                document.getElementById('review-form').submit();
            }
        });
    }

    // If rejection reason group exists, bind a submit on the form when action = reject
    const reviewForm = document.getElementById('review-form');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            const action = document.getElementById('review-action').value;
            if (action === 'reject') {
                const reason = document.getElementById('rejection_reason').value.trim();
                if (!reason) {
                    e.preventDefault();
                    document.getElementById('rejection_reason').focus();
                    alert('Please provide a rejection reason.');
                }
            }
        });
    }
</script>
@endpush

@endsection
