@extends('tyro-dashboard::layouts.admin')

@section('title', 'Review Report #' . $report->id)

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('admin.user-reports.index') }}">User Reports</a>
<span class="breadcrumb-separator">/</span>
<span>Report #{{ $report->id }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Report #{{ $report->id }}</h1>
            <p class="page-description">Review the submitted report and take action.</p>
        </div>
        <a href="{{ route('admin.user-reports.index') }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to List
        </a>
    </div>
</div>

<div class="grid-2">

    {{-- Left: report details + reported content --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">

        {{-- Status --}}
        <div class="card">
            <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    @php
                        $statusBadge = [
                            'open'         => 'badge-warning',
                            'under_review' => 'badge-primary',
                            'resolved'     => 'badge-success',
                            'dismissed'    => 'badge-secondary',
                        ][$report->status] ?? 'badge-secondary';
                    @endphp
                    <span style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Status</span>
                    <div style="margin-top: 0.375rem;">
                        <span class="badge {{ $statusBadge }}" style="font-size: 0.95rem; padding: 0.35rem 0.9rem;">
                            {{ str_replace('_', ' ', ucfirst($report->status)) }}
                        </span>
                    </div>
                </div>
                <div style="font-size: 0.875rem; color: var(--muted-foreground); text-align: right;">
                    Submitted {{ $report->created_at->format('M d, Y H:i') }}
                    @if($report->resolver)
                        <br>Resolved by {{ $report->resolver->name }} on {{ $report->resolved_at?->format('M d, Y') }}
                    @endif
                </div>
            </div>
        </div>

        {{-- Report details --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Report Details</h3>
            </div>
            <div class="card-body">
                <dl style="display: grid; gap: 1rem; font-size: 0.875rem;">
                    <div>
                        <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Reason</dt>
                        <dd>
                            <span class="badge badge-secondary" style="text-transform: capitalize; font-size: 0.875rem;">
                                {{ str_replace('_', ' ', $report->reason) }}
                            </span>
                        </dd>
                    </div>
                    @if($report->description)
                    <div>
                        <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Description</dt>
                        <dd style="color: var(--foreground); white-space: pre-line; line-height: 1.6;">{{ $report->description }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Reported by</dt>
                        <dd>{{ $report->reporter->name ?? '—' }} ({{ $report->reporter->email ?? '' }})</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Reported content preview --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Reported Content</h3>
            </div>
            <div class="card-body">
                @if($report->reportable instanceof \App\Models\Campaign)
                    <dl style="display: grid; gap: 0.75rem; font-size: 0.875rem;">
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Campaign</dt>
                            <dd style="font-weight: 500; margin-top: 0.2rem;">{{ $report->reportable->title }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Status</dt>
                            <dd style="margin-top: 0.2rem;"><span class="badge badge-secondary">{{ ucfirst($report->reportable->status) }}</span></dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Organizer</dt>
                            <dd style="margin-top: 0.2rem;">{{ $report->reportable->organizerProfile?->full_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Description</dt>
                            <dd style="margin-top: 0.2rem; color: var(--muted-foreground);">{{ $report->reportable->short_description }}</dd>
                        </div>
                    </dl>
                    <div style="margin-top: 1.25rem; display: flex; gap: 0.75rem;">
                        <a href="{{ route('campaigns.show', $report->reportable->slug) }}"
                            target="_blank"
                            class="btn btn-secondary" style="font-size: 0.8rem;">
                            View Public Page ↗
                        </a>
                        <a href="{{ route('admin.campaigns.show', $report->reportable) }}"
                            class="btn btn-secondary" style="font-size: 0.8rem;">
                            Moderate Campaign
                        </a>
                    </div>
                @elseif($report->reportable instanceof \App\Models\Donation)
                    <dl style="display: grid; gap: 0.75rem; font-size: 0.875rem;">
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Donation #</dt>
                            <dd style="margin-top: 0.2rem; font-weight: 500;">{{ $report->reportable->id }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Amount</dt>
                            <dd style="margin-top: 0.2rem;">{{ $report->reportable->currency }} {{ number_format($report->reportable->gross_amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Donor</dt>
                            <dd style="margin-top: 0.2rem;">{{ $report->reportable->resolvedDonorName() }}</dd>
                        </div>
                        @if($report->reportable->message)
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Message</dt>
                            <dd style="margin-top: 0.2rem; color: var(--muted-foreground);">{{ $report->reportable->message }}</dd>
                        </div>
                        @endif
                    </dl>
                @else
                    <p style="color: var(--muted-foreground); font-size: 0.875rem;">
                        Content type: {{ class_basename($report->reportable_type ?? 'Unknown') }} #{{ $report->reportable_id }}
                        @if(! $report->reportable)
                            <br><span style="color: var(--destructive);">(Content no longer exists)</span>
                        @endif
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Right: action panel --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">

        @if(! in_array($report->status, ['resolved', 'dismissed']))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Take Action</h3>
            </div>
            <div class="card-body">
                <p style="font-size: 0.875rem; color: var(--muted-foreground); margin-bottom: 1.25rem;">
                    Choose how to handle this report. The reporter is not notified of the outcome.
                </p>
                <form action="{{ route('admin.user-reports.resolve', $report) }}" method="POST" id="resolve-form">
                    @csrf
                    <input type="hidden" name="action" id="resolve-action" value="">
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        @if($report->status !== 'under_review')
                        <button type="button" class="btn btn-secondary"
                            onclick="submitResolve('under_review')">
                            Mark Under Review
                        </button>
                        @endif
                        <button type="button" class="btn btn-primary"
                            onclick="submitResolve('resolved')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:1rem;height:1rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Mark Resolved
                        </button>
                        <button type="button" class="btn btn-ghost"
                            onclick="submitResolve('dismissed')">
                            Dismiss
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Quick links to related admin pages --}}
        @if($report->reportable instanceof \App\Models\Campaign)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <a href="{{ route('admin.campaigns.show', $report->reportable) }}" class="btn btn-secondary">
                    Open Campaign Moderation Page
                </a>
                <a href="{{ route('admin.organizer-verification.show', $report->reportable->organizer_profile_id) }}" class="btn btn-secondary">
                    View Organizer Profile
                </a>
            </div>
        </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
    function submitResolve(action) {
        const labels = {
            resolved:     ['Mark Resolved', 'Mark this report as resolved? No further action will be taken.'],
            dismissed:    ['Dismiss Report', 'Dismiss this report as unfounded?'],
            under_review: ['Mark Under Review', 'Mark this report as under review?'],
        };
        const [title, msg] = labels[action] || ['Confirm', 'Are you sure?'];
        showConfirm(title, msg).then(confirmed => {
            if (confirmed) {
                document.getElementById('resolve-action').value = action;
                document.getElementById('resolve-form').submit();
            }
        });
    }
</script>
@endpush
@endsection
