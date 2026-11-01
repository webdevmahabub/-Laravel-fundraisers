@extends('tyro-dashboard::layouts.user')

@section('title', 'Organizer Application')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Organizer Application</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Organizer Application</h1>
            <p class="page-description">Apply to become a verified organizer and start raising funds.</p>
        </div>
        @if($profile && in_array($profile->status, ['pending', 'rejected']))
            <a href="{{ route('organizer.application.edit') }}" class="btn btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit Application
            </a>
        @endif
    </div>
</div>

@if(!$profile)
    {{-- No application yet --}}
    <div class="card">
        <div class="card-body" style="padding: 3rem; text-align: center;">
            <div style="width: 4rem; height: 4rem; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <svg style="width: 2rem; height: 2rem; color: white;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 0.75rem;">Become an Organizer</h2>
            <p style="color: var(--muted-foreground); max-width: 480px; margin: 0 auto 2rem;">
                Submit your information and verification documents. Once approved by our team, you can create and manage fundraising campaigns.
            </p>
            <div style="display: flex; flex-direction: column; gap: 0.75rem; max-width: 400px; margin: 0 auto 2rem; text-align: left;">
                <div style="display: flex; align-items: center; gap: 0.75rem; color: var(--muted-foreground); font-size: 0.9rem;">
                    <svg style="width: 1.25rem; height: 1.25rem; color: var(--primary); flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Fill in your personal information
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem; color: var(--muted-foreground); font-size: 0.9rem;">
                    <svg style="width: 1.25rem; height: 1.25rem; color: var(--primary); flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Upload identity verification documents
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem; color: var(--muted-foreground); font-size: 0.9rem;">
                    <svg style="width: 1.25rem; height: 1.25rem; color: var(--primary); flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Wait for admin review and approval
                </div>
            </div>
            <a href="{{ route('organizer.application.create') }}" class="btn btn-primary" style="font-size: 1rem; padding: 0.75rem 2rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Start Application
            </a>
        </div>
    </div>

@else
    <div class="grid-2">
        {{-- Status Card --}}
        <div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Application Status</h3>
                </div>
                <div class="card-body">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                        @php
                            $statusConfig = [
                                'pending'      => ['label' => 'Pending Review',  'class' => 'badge-warning'],
                                'under_review' => ['label' => 'Under Review',    'class' => 'badge-primary'],
                                'approved'     => ['label' => 'Approved',        'class' => 'badge-success'],
                                'rejected'     => ['label' => 'Rejected',        'class' => 'badge-danger'],
                                'suspended'    => ['label' => 'Suspended',       'class' => 'badge-danger'],
                            ];
                            $cfg = $statusConfig[$profile->status] ?? ['label' => ucfirst($profile->status), 'class' => 'badge-secondary'];
                        @endphp
                        <span class="badge {{ $cfg['class'] }}" style="font-size: 1rem; padding: 0.4rem 1rem;">{{ $cfg['label'] }}</span>
                    </div>

                    @if($profile->status === 'approved')
                        <div style="background: var(--success-muted, #dcfce7); border-radius: 0.5rem; padding: 1rem; color: #166534; font-size: 0.9rem;">
                            <strong>Congratulations!</strong> Your application has been approved on {{ $profile->verified_at?->format('M d, Y') }}. You can now create fundraising campaigns.
                        </div>
                    @elseif($profile->status === 'rejected')
                        <div style="background: #fef2f2; border-radius: 0.5rem; padding: 1rem; color: #991b1b; font-size: 0.9rem; margin-bottom: 1rem;">
                            <strong>Application Rejected.</strong>
                            @if($profile->rejection_reason)
                                <p style="margin-top: 0.5rem;">{{ $profile->rejection_reason }}</p>
                            @endif
                        </div>
                        <p style="color: var(--muted-foreground); font-size: 0.875rem;">
                            You may update your application and resubmit for review.
                        </p>
                    @elseif($profile->status === 'pending')
                        <p style="color: var(--muted-foreground); font-size: 0.875rem;">
                            Your application is awaiting review. We'll notify you once a decision has been made.
                        </p>
                    @elseif($profile->status === 'under_review')
                        <p style="color: var(--muted-foreground); font-size: 0.875rem;">
                            Our team is currently reviewing your application. This usually takes 1–3 business days.
                        </p>
                    @endif

                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border); font-size: 0.875rem; color: var(--muted-foreground);">
                        Submitted: {{ $profile->created_at->format('M d, Y') }}
                    </div>
                </div>
            </div>

            {{-- Profile details --}}
            <div class="card" style="margin-top: 1rem;">
                <div class="card-header">
                    <h3 class="card-title">Your Information</h3>
                </div>
                <div class="card-body">
                    <dl style="display: grid; gap: 0.75rem;">
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Full Name</dt>
                            <dd style="margin-top: 0.25rem; font-weight: 500;">{{ $profile->full_name }}</dd>
                        </div>
                        @if($profile->phone)
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Phone</dt>
                            <dd style="margin-top: 0.25rem;">{{ $profile->phone }}</dd>
                        </div>
                        @endif
                        @if($profile->address)
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Address</dt>
                            <dd style="margin-top: 0.25rem;">{{ $profile->address }}</dd>
                        </div>
                        @endif
                        @if($profile->bio)
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Bio</dt>
                            <dd style="margin-top: 0.25rem;">{{ $profile->bio }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        {{-- Documents --}}
        <div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Verification Documents</h3>
                </div>

                @if(in_array($profile->status, ['pending', 'rejected']))
                    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);">
                        <form action="{{ route('organizer.application.documents.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div style="display: grid; gap: 0.75rem;">
                                <div class="form-group">
                                    <label for="type" class="form-label">Document Type <span style="color: var(--destructive);">*</span></label>
                                    <select name="type" id="type" class="form-select" required>
                                        <option value="">Select type...</option>
                                        <option value="national_id">National ID</option>
                                        <option value="passport">Passport</option>
                                        <option value="drivers_license">Driver's License</option>
                                        <option value="business_registration">Business Registration</option>
                                        <option value="utility_bill">Utility Bill</option>
                                        <option value="other">Other</option>
                                    </select>
                                    @error('type') <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span> @enderror
                                </div>
                                <div class="form-group">
                                    <label for="document" class="form-label">File <span style="color: var(--destructive);">*</span></label>
                                    <input type="file" name="document" id="document" class="form-input" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                                    <small class="form-hint">PDF, JPG, PNG, or WebP — max 10 MB</small>
                                    @error('document') <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span> @enderror
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Upload Document
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <div class="card-body" style="padding: 0;">
                    @if($profile->verificationDocuments->isEmpty())
                        <div class="empty-state" style="padding: 2rem;">
                            <p class="empty-state-description">No documents uploaded yet.</p>
                        </div>
                    @else
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>File</th>
                                        <th>Uploaded</th>
                                        @if(in_array($profile->status, ['pending', 'rejected']))
                                            <th style="text-align: right;">Actions</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($profile->verificationDocuments as $doc)
                                    <tr>
                                        <td>
                                            <span class="badge badge-secondary">{{ str_replace('_', ' ', ucfirst($doc->type)) }}</span>
                                        </td>
                                        <td>
                                            <span style="font-size: 0.875rem;">{{ $doc->original_filename }}</span>
                                            <br>
                                            <small style="color: var(--muted-foreground);">{{ number_format($doc->size / 1024, 1) }} KB</small>
                                        </td>
                                        <td style="font-size: 0.875rem;">{{ $doc->uploaded_at->format('M d, Y') }}</td>
                                        @if(in_array($profile->status, ['pending', 'rejected']))
                                        <td style="text-align: right;">
                                            <form action="{{ route('organizer.application.documents.delete', $doc) }}" method="POST" id="del-doc-{{ $doc->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="action-btn action-btn-danger" title="Remove"
                                                    onclick="showDanger('Remove Document', 'Remove this document from your application?').then(c => { if(c) document.getElementById('del-doc-{{ $doc->id }}').submit(); })">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
