@extends('tyro-dashboard::layouts.admin')

@section('title', 'Withdrawal Management')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Withdrawals</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Withdrawals</h1>
            <p class="page-description">Review and process organizer payout requests.</p>
        </div>
    </div>
</div>

{{-- Status summary stats --}}
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Pending</div>
            <div class="stat-value">{{ $counts['pending'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Processing</div>
            <div class="stat-value">{{ $counts['processing'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Completed</div>
            <div class="stat-value">{{ $counts['completed'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Failed</div>
            <div class="stat-value">{{ $counts['failed'] }}</div>
        </div>
    </div>
</div>

{{-- Status filter tabs --}}
<div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
    @foreach(['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed', 'all' => 'All'] as $key => $label)
        <a href="{{ route('admin.withdrawals.index', ['status' => $key]) }}"
            class="btn {{ $status === $key ? 'btn-primary' : 'btn-secondary' }}"
            style="font-size: 0.875rem; padding: 0.375rem 0.875rem;">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="card">
    @if($withdrawals->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Organizer</th>
                        <th>Amount</th>
                        <th>Fee</th>
                        <th>Net</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($withdrawals as $w)
                    @php
                        $badge = [
                            'pending' => 'badge-warning',
                            'processing' => 'badge-primary',
                            'completed' => 'badge-success',
                            'failed' => 'badge-danger',
                            'rejected' => 'badge-danger',
                        ][$w->status] ?? 'badge-secondary';
                    @endphp
                    <tr>
                        <td>{{ $w->organizerProfile->user->name ?? '—' }}</td>
                        <td>{{ $w->currency }} {{ number_format($w->amount, 2) }}</td>
                        <td style="font-size: 0.875rem; color: var(--muted-foreground);">{{ number_format($w->withdrawal_fee_amount, 2) }}</td>
                        <td style="font-size: 0.875rem;">{{ number_format($w->net_amount, 2) }}</td>
                        <td style="font-size: 0.875rem;">{{ str_replace('_', ' ', ucfirst($w->payment_method ?? '—')) }}</td>
                        <td><span class="badge {{ $badge }}">{{ str_replace('_', ' ', ucfirst($w->status)) }}</span></td>
                        <td style="font-size: 0.875rem; color: var(--muted-foreground);">{{ $w->requested_at->format('M d, Y') }}</td>
                        <td style="text-align: right;">
                            <a href="{{ route('admin.withdrawals.show', $w) }}"
                                class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.75rem;">Review</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($withdrawals->hasPages())
            <div class="pagination">{{ $withdrawals->links() }}</div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="empty-state-title">No withdrawals found</h3>
            <p class="empty-state-description">There are no withdrawal requests with the selected status.</p>
        </div>
    @endif
</div>
@endsection
