@extends('tyro-dashboard::layouts.admin')

@section('title', 'Campaign Moderation')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Campaigns</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Campaign Moderation</h1>
            <p class="page-description">Review, approve, and manage fundraising campaigns.</p>
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
            <div class="stat-label">Active</div>
            <div class="stat-value">{{ $counts['active'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Paused</div>
            <div class="stat-value">{{ $counts['paused'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Suspended</div>
            <div class="stat-value">{{ $counts['suspended'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Rejected</div>
            <div class="stat-value">{{ $counts['rejected'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Completed</div>
            <div class="stat-value">{{ $counts['completed'] }}</div>
        </div>
    </div>
</div>

{{-- Status filter tabs --}}
<div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
    @foreach(['pending' => 'Pending', 'active' => 'Active', 'paused' => 'Paused', 'suspended' => 'Suspended', 'rejected' => 'Rejected', 'completed' => 'Completed', 'all' => 'All'] as $key => $label)
        <a href="{{ route('admin.campaigns.index', ['status' => $key]) }}"
            class="btn {{ $status === $key ? 'btn-primary' : 'btn-secondary' }}"
            style="font-size: 0.875rem; padding: 0.375rem 0.875rem;">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="card">
    @if($campaigns->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Organizer</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Raised</th>
                        <th>Donors</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaigns as $campaign)
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
                    <tr>
                        <td>
                            <div style="font-weight: 500;">{{ $campaign->title }}</div>
                            <div style="font-size: 0.8rem; color: var(--muted-foreground);">{{ $campaign->slug }}</div>
                        </td>
                        <td>{{ $campaign->organizerProfile->user->name ?? '—' }}</td>
                        <td>{{ $campaign->category->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $statusBadge }}">
                                {{ str_replace('_', ' ', ucfirst($campaign->status)) }}
                            </span>
                        </td>
                        <td style="font-size: 0.875rem;">
                            {{ $campaign->currency }} {{ number_format($campaign->raised_amount, 2) }}
                            <span style="color: var(--muted-foreground);">/ {{ number_format($campaign->goal_amount, 2) }}</span>
                        </td>
                        <td style="font-size: 0.875rem;">{{ $campaign->donations_count }}</td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('admin.campaigns.show', $campaign) }}"
                                    class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.75rem;">
                                    Review
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($campaigns->hasPages())
            <div class="pagination">
                {{ $campaigns->links() }}
            </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="empty-state-title">No campaigns found</h3>
            <p class="empty-state-description">There are no campaigns with the selected status.</p>
        </div>
    @endif
</div>
@endsection
