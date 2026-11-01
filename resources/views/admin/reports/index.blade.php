@extends('tyro-dashboard::layouts.admin')

@section('title', 'Reports')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Reports</span>
@endsection

@php
    $c = $report['currency'];
    $fmt = fn (float $v) => $c . ' ' . number_format($v, 2);
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Platform Reports</h1>
            <p class="page-description">Financial and operational summary across all campaigns. All figures are derived from completed, immutable transactions.</p>
        </div>
    </div>
</div>

<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Total Raised</div>
            <div class="stat-value">{{ $fmt($report['totals']['gross_raised']) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Net to Organizers</div>
            <div class="stat-value">{{ $fmt($report['totals']['net_to_organizers']) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Platform Revenue</div>
            <div class="stat-value">{{ $fmt($report['totals']['platform_revenue']) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Pending Withdrawals</div>
            <div class="stat-value">{{ $fmt($report['pending_withdrawals']['amount']) }}</div>
            <div class="stat-sub">{{ $report['pending_withdrawals']['count'] }} awaiting review</div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Fee Breakdown</h2>
    </div>
    <div class="table-container">
        <table class="table">
            <tbody>
                <tr>
                    <td>Gross raised (completed donations)</td>
                    <td style="text-align: right;">{{ $fmt($report['totals']['gross_raised']) }}</td>
                </tr>
                <tr>
                    <td>Platform fees collected</td>
                    <td style="text-align: right;">{{ $fmt($report['totals']['platform_fees']) }}</td>
                </tr>
                <tr>
                    <td>Payment processing fees</td>
                    <td style="text-align: right;">{{ $fmt($report['totals']['processing_fees']) }}</td>
                </tr>
                <tr>
                    <td>Net disbursed to organizers (donations)</td>
                    <td style="text-align: right;">{{ $fmt($report['totals']['net_to_organizers']) }}</td>
                </tr>
                <tr>
                    <td>Withdrawn (gross)</td>
                    <td style="text-align: right;">{{ $fmt($report['totals']['withdrawn_gross']) }}</td>
                </tr>
                <tr>
                    <td>Withdrawal fees collected</td>
                    <td style="text-align: right;">{{ $fmt($report['totals']['withdrawal_fees']) }}</td>
                </tr>
                <tr>
                    <td>Net disbursed (withdrawals)</td>
                    <td style="text-align: right;">{{ $fmt($report['totals']['net_disbursed']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Donations</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Donor</th>
                        <th>Campaign</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['recent_donations'] as $d)
                        <tr>
                            <td>{{ $d->resolvedDonorName() }}</td>
                            <td style="font-size: 0.85rem;">{{ $d->campaign->title ?? '—' }}</td>
                            <td style="text-align: right;">{{ $fmt((float) $d->gross_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No donations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Withdrawals</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Organizer</th>
                        <th>Status</th>
                        <th style="text-align: right;">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['recent_withdrawals'] as $w)
                        <tr>
                            <td>{{ $w->organizerProfile->user->name ?? '—' }}</td>
                            <td><span class="badge badge-secondary">{{ str_replace('_', ' ', ucfirst($w->status)) }}</span></td>
                            <td style="text-align: right;">{{ $fmt((float) $w->net_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No withdrawals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
