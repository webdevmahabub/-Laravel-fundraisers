@extends('tyro-dashboard::layouts.admin')

@section('title', 'Admin Dashboard')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Overview</span>
@endsection

@php
    $c = $report['currency'];
    $fmt = fn (float $v) => $c . ' ' . number_format($v, 2);
    $pctFmt = fn (float $v) => number_format($v, 1) . '%';
@endphp

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-description">Platform overview across all campaigns and donations.</p>
        </div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="stats-grid" style="margin-bottom:1.5rem;">
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

{{-- Period Reports --}}
<div class="stats-grid" style="margin-bottom:1.5rem;">
    @foreach($charts['periods'] as $period)
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">{{ $period['label'] }}</div>
            <div class="stat-value">{{ $fmt($period['amount']) }}</div>
            <div class="stat-sub">{{ $period['count'] }} donations</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Counts Row --}}
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Total Campaigns</div>
            <div class="stat-value">{{ $report['counts']['campaigns'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Active Campaigns</div>
            <div class="stat-value">{{ $report['counts']['active_campaigns'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Total Donors</div>
            <div class="stat-value">{{ number_format($report['counts']['donors']) }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Completed Donations</div>
            <div class="stat-value">{{ number_format($report['counts']['donations_completed']) }}</div>
        </div>
    </div>
</div>

{{-- Donation Trend --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Donation Trend</h2>
        <span style="margin-left:auto;font-size:0.8rem;color:var(--text-muted-foreground);">
            {{ $charts['trend']['range_start'] }} – {{ $charts['trend']['range_end'] }}
        </span>
    </div>
    <div class="card-body">
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
            <span style="font-size:1.5rem;font-weight:700;">{{ $fmt($charts['trend']['total']) }}</span>
            @if($charts['trend']['growth_pct'] != 0)
            <span class="badge {{ $charts['trend']['growth_pct'] > 0 ? 'badge-success' : 'badge-danger' }}">
                {{ $charts['trend']['growth_pct'] > 0 ? '+' : '' }}{{ $pctFmt($charts['trend']['growth_pct']) }}
            </span>
            @endif
        </div>
        <svg viewBox="0 0 600 180" style="width:100%;height:auto;">
            <path d="{{ $charts['trend']['area_path'] }}" fill="var(--primary)" opacity="0.08"/>
            <path d="{{ $charts['trend']['line_path'] }}" fill="none" stroke="var(--primary)" stroke-width="2.5"/>
        </svg>
    </div>
</div>

{{-- Charts Row: Weekly + Campaign Status --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

    {{-- Weekly Donations --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Weekly Donations</h2>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:flex-end;gap:0.5rem;height:120px;">
                @foreach($charts['weekly'] as $day)
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.25rem;">
                    <div style="font-size:0.65rem;color:var(--text-muted-foreground);font-weight:500;">{{ $c }} {{ number_format($day['value'], 0) }}</div>
                    <div style="width:100%;height:{{ max(4, $day['pct']) }}%;background:var(--primary);border-radius:4px;min-height:4px;"></div>
                    <div style="font-size:0.7rem;color:var(--text-secondary);font-weight:500;">{{ $day['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Campaign Status Donut --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Campaign Status</h2>
            <span style="margin-left:auto;font-size:0.8rem;color:var(--text-muted-foreground);">
                {{ $charts['status_donut']['total'] }} total
            </span>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:1.5rem;">
                <svg viewBox="0 0 120 120" style="width:100px;height:100px;flex-shrink:0;">
                    @php($offset = 0)
                    @foreach($charts['status_donut']['slices'] as $slice)
                        <circle cx="60" cy="60" r="50"
                            fill="none"
                            stroke="{{ $slice['color'] }}"
                            stroke-width="20"
                            stroke-dasharray="{{ ($slice['pct'] / 100) * 314.16 }} {{ 314.16 - (($slice['pct'] / 100) * 314.16) }}"
                            stroke-dashoffset="{{ -($offset / 100) * 314.16 }}"
                            transform="rotate(-90 60 60)"/>
                        @php($offset += $slice['pct'])
                    @endforeach
                </svg>
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    @foreach($charts['status_donut']['slices'] as $slice)
                    <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8rem;">
                        <div style="width:10px;height:10px;border-radius:2px;background:{{ $slice['color'] }};flex-shrink:0;"></div>
                        <span style="font-weight:500;">{{ $slice['label'] }}</span>
                        <span style="color:var(--text-muted-foreground);">{{ $slice['count'] }} ({{ $slice['pct'] }}%)</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Top Campaigns Progress --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Top Campaigns</h2>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:1rem;">
        @foreach($charts['progress'] as $item)
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.375rem;">
                <div>
                    <span style="font-weight:600;font-size:0.875rem;">{{ $item['title'] }}</span>
                    <span class="badge {{ $item['badge_class'] }}" style="margin-left:0.5rem;font-size:0.65rem;">{{ $item['badge_text'] }}</span>
                </div>
                <span style="font-size:0.75rem;color:var(--text-muted-foreground);">{{ $item['meta'] }}</span>
            </div>
            <div style="font-size:0.75rem;color:var(--text-secondary);margin-bottom:0.375rem;">{{ $item['subtitle'] }}</div>
            <div style="background:var(--border);border-radius:999px;height:6px;overflow:hidden;">
                <div style="background:{{ $item['bar_color'] }};height:6px;width:{{ $item['pct'] }}%;border-radius:999px;"></div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Top Categories --}}
@if($charts['top_categories'] !== [])
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header">
        <h2 class="card-title">Top Categories</h2>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:0.75rem;">
        @foreach($charts['top_categories'] as $cat)
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <span style="font-size:0.8125rem;font-weight:500;width:140px;flex-shrink:0;">{{ $cat['label'] }}</span>
            <div style="flex:1;background:var(--border);border-radius:999px;height:8px;overflow:hidden;">
                <div style="background:{{ $cat['color'] }};height:8px;width:{{ $cat['pct'] }}%;border-radius:999px;"></div>
            </div>
            <span style="font-size:0.8125rem;font-weight:600;width:80px;text-align:right;">{{ $fmt($cat['value']) }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Recent Donations & Withdrawals --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Donations</h2>
            <span style="margin-left:auto;font-size:0.75rem;color:var(--text-muted-foreground);">Last 8</span>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Donor</th>
                        <th>Campaign</th>
                        <th>Method</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['recent_donations'] as $d)
                        <tr>
                            <td style="white-space:nowrap;font-size:0.8rem;color:var(--text-muted-foreground);">{{ $d->donated_at?->format('M j, g:ia') ?? '—' }}</td>
                            <td>
                                <div style="font-weight:500;font-size:0.875rem;">{{ $d->resolvedDonorName() }}</div>
                                <div style="font-size:0.75rem;color:var(--text-muted-foreground);">{{ $d->donor_email }}</div>
                            </td>
                            <td style="font-size:0.85rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $d->campaign->title ?? '—' }}</td>
                            <td style="font-size:0.8rem;">{{ $d->payment_method ?: '—' }}</td>
                            <td style="text-align:right;font-weight:600;">{{ $fmt((float) $d->gross_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No donations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Withdrawals</h2>
            <span style="margin-left:auto;font-size:0.75rem;color:var(--text-muted-foreground);">Last 8</span>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Organizer</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th style="text-align:right;">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['recent_withdrawals'] as $w)
                        <tr>
                            <td style="white-space:nowrap;font-size:0.8rem;color:var(--text-muted-foreground);">{{ $w->requested_at?->format('M j, g:ia') ?? '—' }}</td>
                            <td style="font-weight:500;">{{ $w->organizerProfile->user->name ?? '—' }}</td>
                            <td style="font-size:0.8rem;">{{ $w->payment_method ?: '—' }}</td>
                            <td>
                                @php
                                    $statusClass = match($w->status) {
                                        'completed' => 'badge-green',
                                        'pending' => 'badge-yellow',
                                        'failed' => 'badge-red',
                                        default => 'badge-gray',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ str_replace('_', ' ', ucfirst($w->status)) }}</span>
                            </td>
                            <td style="text-align:right;font-weight:600;">{{ $fmt((float) $w->net_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No withdrawals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
