@extends('tyro-dashboard::layouts.admin')

@section('title', 'Donation Verification')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Donations</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Donation Verification</h1>
            <p class="page-description">Check manual transfers (bKash, Nagad, bank) against the donor's TrxID before verifying. Verified donations are added to the campaign total.</p>
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
            <div class="stat-label">Completed</div>
            <div class="stat-value">{{ $counts['completed'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Cancelled</div>
            <div class="stat-value">{{ $counts['cancelled'] }}</div>
        </div>
    </div>
</div>

{{-- Status filter tabs --}}
<div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
    @foreach(['pending' => 'Pending', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'all' => 'All'] as $key => $label)
        <a href="{{ route('admin.donations.index', ['status' => $key]) }}"
            class="btn {{ $status === $key ? 'btn-primary' : 'btn-secondary' }}"
            style="font-size: 0.875rem; padding: 0.375rem 0.875rem;">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="card">
    @if($donations->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Donor</th>
                        <th>Campaign</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>TrxID</th>
                        <th>Sender mobile</th>
                        <th>Note</th>
                        <th>Submitted</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($donations as $donation)
                    @php
                        $statusBadge = [
                            'pending'   => 'badge-warning',
                            'completed' => 'badge-success',
                            'cancelled' => 'badge-secondary',
                        ][$donation->status] ?? 'badge-secondary';
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight: 500;">{{ $donation->resolvedDonorName() }}</div>
                            <div style="font-size: 0.8rem; color: var(--muted-foreground);">
                                {{ $donation->is_anonymous ? 'Anonymous gift' : ($donation->donor_email ?? 'Guest') }}
                            </div>
                        </td>
                        <td>
                            @if ($donation->campaign)
                                <div style="font-weight: 500;">{{ $donation->campaign->title }}</div>
                                <div style="font-size: 0.8rem; color: var(--muted-foreground);">{{ $donation->campaign->slug }}</div>
                            @else
                                <span style="color: var(--muted-foreground);">Campaign deleted</span>
                            @endif
                        </td>
                        <td style="font-size: 0.875rem;">
                            {{ $donation->currency }} {{ number_format($donation->gross_amount, 2) }}
                        </td>
                        <td style="font-size: 0.875rem;">
                            {{ $donation->payment_method ?? '—' }}
                        </td>
                        <td style="font-size: 0.875rem; font-family: monospace;">
                            {{ $donation->gateway_transaction_id ?? '—' }}
                        </td>
                        <td style="font-size: 0.875rem;">
                            {{ $donation->sender_mobile ?? '—' }}
                        </td>
                        <td style="font-size: 0.8rem; max-width: 220px;">
                            {{ $donation->message ? Str::limit($donation->message, 80) : '—' }}
                        </td>
                        <td>
                            <span class="badge {{ $statusBadge }}">{{ ucfirst($donation->status) }}</span>
                            <div style="font-size: 0.8rem; color: var(--muted-foreground); margin-top: 0.25rem;">
                                {{ $donation->created_at->format('d M Y H:i') }}
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                @if ($donation->status === 'pending')
                                    <form method="POST" action="{{ route('admin.donations.verify', $donation) }}" style="display: inline;">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <button type="submit" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.3rem 0.75rem;">
                                            Verify
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.donations.reject', $donation) }}" style="display: inline;"
                                          onsubmit="return confirm('Reject this donation? The transfer could not be confirmed.');">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $status }}">
                                        <button type="submit" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.75rem;">
                                            Reject
                                        </button>
                                    </form>
                                @else
                                    <span style="font-size: 0.8rem; color: var(--muted-foreground);">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($donations->hasPages())
            <div class="pagination">
                {{ $donations->links() }}
            </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m4 0h1M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <h3 class="empty-state-title">No donations found</h3>
            <p class="empty-state-description">There are no donations with the selected status.</p>
        </div>
    @endif
</div>
@endsection
