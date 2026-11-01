@extends('tyro-dashboard::layouts.user')

@section('title', 'Withdrawals')

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
            <p class="page-description">Request payouts from your campaign earnings.</p>
        </div>
    </div>
</div>

{{-- Available balance + fee summary --}}
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Available Balance</div>
            <div class="stat-value">{{ $withdrawals->first()?->currency ?? 'USD' }} {{ number_format($available, 2) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Withdrawal Fee</div>
            <div class="stat-value">{{ $feePct }}%</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Minimum</div>
            <div class="stat-value">{{ number_format($minimum, 2) }}</div>
        </div>
    </div>
</div>

@if($available >= $minimum)
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">Request a Withdrawal</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('organizer.withdrawals.store') }}" method="POST">
            @csrf
            <div class="grid-2" style="gap: 1rem;">
                <div class="form-group">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" name="amount" id="amount" class="form-input" step="0.01"
                        min="{{ $minimum }}" max="{{ $available }}" placeholder="0.00"
                        value="{{ old('amount') }}" required>
                    @error('amount')
                        <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select name="payment_method" id="payment_method" class="form-select" required>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="paypal">PayPal</option>
                        <option value="stripe">Stripe</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-top: 1rem;">
                <label for="account_details" class="form-label">Account Details <span class="form-label-optional">(optional)</span></label>
                <textarea name="account_details" id="account_details" class="form-textarea" rows="3"
                    placeholder="e.g. account number, PayPal email, etc.">{{ old('account_details') }}</textarea>
            </div>
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn btn-primary">Submit Withdrawal Request</button>
            </div>
        </form>
    </div>
</div>
@else
<div class="card" style="margin-bottom: 1.5rem; border-color: var(--border);">
    <div class="card-body" style="color: var(--muted-foreground);">
        You need at least {{ number_format($minimum, 2) }} in available balance (from completed donations) to request a withdrawal.
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Withdrawal History</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        @if($withdrawals->count())
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Fee</th>
                            <th>Net</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th style="text-align: right;">Action</th>
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
                            <td>{{ $w->currency }} {{ number_format($w->amount, 2) }}</td>
                            <td style="font-size: 0.875rem; color: var(--muted-foreground);">{{ number_format($w->withdrawal_fee_amount, 2) }}</td>
                            <td style="font-size: 0.875rem;">{{ number_format($w->net_amount, 2) }}</td>
                            <td style="font-size: 0.875rem;">{{ str_replace('_', ' ', ucfirst($w->payment_method ?? '—')) }}</td>
                            <td><span class="badge {{ $badge }}">{{ str_replace('_', ' ', ucfirst($w->status)) }}</span></td>
                            <td style="font-size: 0.875rem; color: var(--muted-foreground);">{{ $w->requested_at->format('M d, Y') }}</td>
                            <td style="text-align: right;">
                                @if($w->status === 'pending')
                                <form action="{{ route('organizer.withdrawals.cancel', $w) }}" method="POST" onsubmit="return confirm('Cancel this withdrawal request?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.75rem;">Cancel</button>
                                </form>
                                @endif
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
            <div class="empty-state" style="padding: 2rem;">
                <p class="empty-state-description">You have not requested any withdrawals yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection
