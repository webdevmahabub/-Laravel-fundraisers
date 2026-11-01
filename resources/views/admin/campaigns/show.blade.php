@extends('tyro-dashboard::layouts.admin')

@section('title', 'Review Campaign — ' . $campaign->title)

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('admin.campaigns.index') }}">Campaigns</a>
<span class="breadcrumb-separator">/</span>
<span>{{ $campaign->title }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">{{ $campaign->title }}</h1>
            <p class="page-description">Review the campaign and take moderation actions.</p>
        </div>
        <a href="{{ route('admin.campaigns.index') }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to List
        </a>
    </div>
</div>

<div class="grid-2">

    {{-- Left column: campaign details --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">

        <div class="card">
            <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    @php
                        $statusBadge = [
                            'draft'      => 'badge-secondary',
                            'pending'     => 'badge-warning',
                            'active'      => 'badge-success',
                            'paused'      => 'badge-primary',
                            'suspended'   => 'badge-danger',
                            'rejected'    => 'badge-danger',
                            'completed'   => 'badge-success',
                            'cancelled'   => 'badge-secondary',
                        ][$campaign->status] ?? 'badge-secondary';
                    @endphp
                    <span style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Current Status</span>
                    <div style="margin-top: 0.375rem;">
                        <span class="badge {{ $statusBadge }}" style="font-size: 0.95rem; padding: 0.35rem 0.9rem;">
                            {{ str_replace('_', ' ', ucfirst($campaign->status)) }}
                        </span>
                    </div>
                </div>
                <div style="font-size: 0.875rem; color: var(--muted-foreground); text-align: right;">
                    Created {{ $campaign->created_at->format('M d, Y') }}<br>
                    Organizer: {{ $campaign->organizerProfile->user->name ?? '—' }}
                </div>
            </div>
        </div>

        @if($campaign->cover_image_path)
        <div class="card">
            <div class="card-body" style="padding: 0;">
                <img src="{{ asset('storage/'.$campaign->cover_image_path) }}" alt="{{ $campaign->title }}"
                    style="width: 100%; max-height: 320px; object-fit: cover; border-radius: var(--radius);">
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Campaign Information</h3>
            </div>
            <div class="card-body">
                <dl style="display: grid; gap: 1rem;">
                    <div class="grid-2" style="gap: 1rem;">
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Goal</dt>
                            <dd style="margin-top: 0.25rem; font-weight: 500;">{{ $campaign->currency }} {{ number_format($campaign->goal_amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Raised</dt>
                            <dd style="margin-top: 0.25rem; font-weight: 500;">{{ $campaign->currency }} {{ number_format($campaign->raised_amount, 2) }}</dd>
                        </div>
                    </div>
                    <div class="grid-2" style="gap: 1rem;">
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Progress</dt>
                            <dd style="margin-top: 0.25rem;">{{ $campaign->progressPercentage() }}%</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Donors</dt>
                            <dd style="margin-top: 0.25rem;">{{ $donorsCount }}</dd>
                        </div>
                    </div>
                    <div class="grid-2" style="gap: 1rem;">
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Category</dt>
                            <dd style="margin-top: 0.25rem;">{{ $campaign->category->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Verified</dt>
                            <dd style="margin-top: 0.25rem;">{{ $campaign->is_verified ? 'Yes' : 'No' }}</dd>
                        </div>
                    </div>
                    @if($campaign->start_date || $campaign->end_date)
                    <div class="grid-2" style="gap: 1rem;">
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Start Date</dt>
                            <dd style="margin-top: 0.25rem;">{{ $campaign->start_date?->format('M d, Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">End Date</dt>
                            <dd style="margin-top: 0.25rem;">{{ $campaign->end_date?->format('M d, Y') ?? '—' }}</dd>
                        </div>
                    </div>
                    @endif
                    <div>
                        <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Short Description</dt>
                        <dd style="margin-top: 0.25rem;">{{ $campaign->short_description }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Story</h3>
            </div>
            <div class="card-body" style="color: var(--muted-foreground); white-space: pre-line;">{{ $campaign->story }}</div>
        </div>

        @if($campaign->updates->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Updates ({{ $campaign->updates->count() }})</h3>
            </div>
            <div class="card-body" style="display: flex; flex-direction: column; gap: 0.75rem;">
                @foreach($campaign->updates as $update)
                    <div style="border-left: 3px solid var(--border); padding-left: 0.75rem;">
                        <div style="font-weight: 500;">{{ $update->title }}</div>
                        <div style="font-size: 0.85rem; color: var(--muted-foreground); white-space: pre-line;">{{ $update->body }}</div>
                        <div style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.25rem;">{{ $update->created_at->format('M d, Y') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Right column: moderation actions + audit log --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">

        @if(in_array($campaign->status, ['pending', 'active', 'paused', 'suspended', 'rejected']))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Moderation</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.campaigns.moderate', $campaign) }}" method="POST" id="moderate-form">
                    @csrf
                    <input type="hidden" name="action" id="moderate-action" value="">

                    <div class="form-group" style="margin-bottom: 1.25rem; display: none;" id="rejection-reason-group">
                        <label for="rejection_reason" class="form-label">
                            Rejection Reason <span style="color: var(--destructive);">*</span>
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason"
                            class="form-textarea @error('rejection_reason') is-invalid @enderror"
                            rows="3" placeholder="Explain why this campaign is being rejected...">{{ old('rejection_reason') }}</textarea>
                        @error('rejection_reason')
                            <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                        @enderror
                    </div>

                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        @if(in_array($campaign->status, ['pending', 'rejected']))
                        <button type="button" class="btn btn-primary" onclick="submitModeration('approve')">Approve</button>
                        @endif
                        @if($campaign->status === 'active')
                        <button type="button" class="btn btn-secondary" onclick="submitModeration('pause')">Pause</button>
                        <button type="button" class="btn btn-destructive" onclick="submitModeration('suspend')">Suspend</button>
                        @endif
                        @if($campaign->status === 'paused')
                        <button type="button" class="btn btn-primary" onclick="submitModeration('reactivate')">Reactivate</button>
                        <button type="button" class="btn btn-destructive" onclick="submitModeration('suspend')">Suspend</button>
                        @endif
                        @if($campaign->status === 'suspended')
                        <button type="button" class="btn btn-primary" onclick="submitModeration('reactivate')">Reactivate</button>
                        @endif
                        @if($campaign->status !== 'rejected')
                        <button type="button" class="btn btn-destructive" onclick="showRejectForm()">Reject</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        @endif

        @if($campaign->rejection_reason)
        <div class="card" style="border-color: var(--destructive);">
            <div class="card-header">
                <h3 class="card-title" style="color: var(--destructive);">Rejection Reason</h3>
            </div>
            <div class="card-body" style="color: var(--muted-foreground);">{{ $campaign->rejection_reason }}</div>
        </div>
        @endif

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
                                <td style="font-size: 0.875rem;">{{ $log->user?->name ?? 'System' }}</td>
                                <td style="font-size: 0.875rem; color: var(--muted-foreground);">{{ $log->created_at->format('M d, Y H:i') }}</td>
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
        document.getElementById('moderate-action').value = 'reject';
    }

    function submitModeration(action) {
        if (action === 'reject') {
            showRejectForm();
            return;
        }

        const confirmMessages = {
            approve: ['Approve Campaign', 'Make this campaign publicly visible and active?'],
            pause: ['Pause Campaign', 'Pause donations on this campaign?'],
            suspend: ['Suspend Campaign', 'Suspend this campaign? It will no longer be publicly visible.'],
            reactivate: ['Reactivate Campaign', 'Reactivate this campaign and make it active again?'],
        };

        const [title, msg] = confirmMessages[action] || ['Confirm', 'Are you sure?'];
        showConfirm(title, msg).then(confirmed => {
            if (confirmed) {
                document.getElementById('moderate-action').value = action;
                document.getElementById('moderate-form').submit();
            }
        });
    }

    const moderateForm = document.getElementById('moderate-form');
    if (moderateForm) {
        moderateForm.addEventListener('submit', function(e) {
            const action = document.getElementById('moderate-action').value;
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
