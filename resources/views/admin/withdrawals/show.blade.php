@extends('tyro-dashboard::layouts.admin')

@section('title', 'Review Withdrawal — ' . $withdrawal->organizerProfile->user->name ?? 'Organizer')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('admin.withdrawals.index') }}">Withdrawals</a>
<span class="breadcrumb-separator">/</span>
<span>#{{ $withdrawal->id }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Withdrawal #{{ $withdrawal->id }}</h1>
            <p class="page-description">Review the payout request and process it.</p>
        </div>
        <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to List
        </a>
    </div>
</div>

<div class="grid-2">

    {{-- Left: details --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <div class="card">
            <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div>
                    @php
                        $badge = [
                            'pending' => 'badge-warning',
                            'processing' => 'badge-primary',
                            'completed' => 'badge-success',
                            'failed' => 'badge-danger',
                            'rejected' => 'badge-danger',
                        ][$withdrawal->status] ?? 'badge-secondary';
                    @endphp
                    <span style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Current Status</span>
                    <div style="margin-top: 0.375rem;">
                        <span class="badge {{ $badge }}" style="font-size: 0.95rem; padding: 0.35rem 0.9rem;">
                            {{ str_replace('_', ' ', ucfirst($withdrawal->status)) }}
                        </span>
                    </div>
                </div>
                <div style="font-size: 0.875rem; color: var(--muted-foreground); text-align: right;">
                    Requested {{ $withdrawal->requested_at->format('M d, Y H:i') }}<br>
                    @if($withdrawal->completed_at) Completed {{ $withdrawal->completed_at->format('M d, Y H:i') }} @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Payout Breakdown</h3>
            </div>
            <div class="card-body">
                <dl style="display: grid; gap: 1rem;">
                    <div class="grid-2" style="gap: 1rem;">
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Gross Amount</dt>
                            <dd style="margin-top: 0.25rem; font-weight: 500;">{{ $withdrawal->currency }} {{ number_format($withdrawal->amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Platform Fee</dt>
                            <dd style="margin-top: 0.25rem; font-weight: 500;">{{ $withdrawal->currency }} {{ number_format($withdrawal->withdrawal_fee_amount, 2) }}</dd>
                        </div>
                    </div>
                    <div class="grid-2" style="gap: 1rem;">
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Net to Organizer</dt>
                            <dd style="margin-top: 0.25rem; font-weight: 500;">{{ $withdrawal->currency }} {{ number_format($withdrawal->net_amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Method</dt>
                            <dd style="margin-top: 0.25rem;">{{ str_replace('_', ' ', ucfirst($withdrawal->payment_method ?? '—')) }}</dd>
                        </div>
                    </div>
                    @if(!empty($withdrawal->payment_details['details']))
                    <div>
                        <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Account Details</dt>
                        <dd style="margin-top: 0.25rem; white-space: pre-line;">{{ $withdrawal->payment_details['details'] }}</dd>
                    </div>
                    @endif
                    @if($withdrawal->failed_reason)
                    <div>
                        <dt style="font-size: 0.75rem; color: var(--muted-foreground); text-transform: uppercase; letter-spacing: 0.05em;">Failed Reason</dt>
                        <dd style="margin-top: 0.25rem; color: var(--destructive);">{{ $withdrawal->failed_reason }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Organizer</h3>
            </div>
            <div class="card-body">
                <div style="font-weight: 500;">{{ $withdrawal->organizerProfile->user->name ?? '—' }}</div>
                <div style="font-size: 0.85rem; color: var(--muted-foreground);">{{ $withdrawal->organizerProfile->user->email ?? '' }}</div>
                <div style="font-size: 0.85rem; margin-top: 0.25rem;">{{ $withdrawal->organizerProfile->full_name }}</div>
            </div>
        </div>
    </div>

    {{-- Right: actions + audit --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        @if(in_array($withdrawal->status, ['pending', 'processing']))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Process Withdrawal</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.withdrawals.complete', $withdrawal) }}" method="POST" style="margin-bottom: 1rem;">
                    @csrf
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Mark this withdrawal as completed and record the ledger entry?');">
                        Mark Completed
                    </button>
                </form>

                <div id="fail-group" style="display: none;">
                    <form action="{{ route('admin.withdrawals.fail', $withdrawal) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="failed_reason" class="form-label">Failure Reason <span style="color: var(--destructive);">*</span></label>
                            <textarea name="failed_reason" id="failed_reason" class="form-textarea @error('failed_reason') is-invalid @enderror"
                                rows="3" placeholder="Explain why this withdrawal failed...">{{ old('failed_reason') }}</textarea>
                            @error('failed_reason')
                                <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-destructive" style="margin-top: 0.5rem;">Confirm Failure</button>
                    </form>
                </div>

                @if(!$errors->has('failed_reason'))
                <button type="button" class="btn btn-destructive" style="margin-top: 0.5rem;" onclick="document.getElementById('fail-group').style.display='block'; this.style.display='none';">
                    Mark Failed
                </button>
                @endif
            </div>
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
                                <td><span class="badge badge-secondary" style="font-family: monospace; font-size: 0.75rem;">{{ $log->event }}</span></td>
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
@endsection
