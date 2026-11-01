@extends('tyro-dashboard::layouts.user')

@section('title', 'My Campaigns')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>My Campaigns</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">My Campaigns</h1>
            <p class="page-description">Manage your fundraising campaigns.</p>
        </div>
        <a href="{{ route('organizer.campaigns.create') }}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            New Campaign
        </a>
    </div>
</div>

<div class="card">
    @if($campaigns->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Category</th>
                        <th>Goal</th>
                        <th>Raised</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaigns as $campaign)
                    @php
                        $statusBadge = [
                            'draft'     => 'badge-secondary',
                            'pending'   => 'badge-warning',
                            'active'    => 'badge-success',
                            'paused'    => 'badge-warning',
                            'completed' => 'badge-primary',
                            'cancelled' => 'badge-danger',
                            'rejected'  => 'badge-danger',
                        ][$campaign->status] ?? 'badge-secondary';
                    @endphp
                    <tr>
                        <td>
                            <div class="user-cell-info">
                                <div class="user-cell-name">
                                    <a href="{{ route('organizer.campaigns.show', $campaign) }}" style="text-decoration: none; color: inherit;">
                                        {{ $campaign->title }}
                                    </a>
                                </div>
                                <div class="user-cell-email">{{ $campaign->short_description }}</div>
                            </div>
                        </td>
                        <td>
                            @if($campaign->category)
                                <span class="badge badge-secondary">{{ $campaign->category->name }}</span>
                            @else
                                <span style="color: var(--muted-foreground);">—</span>
                            @endif
                        </td>
                        <td style="font-weight: 500;">
                            {{ $campaign->currency }} {{ number_format($campaign->goal_amount, 2) }}
                        </td>
                        <td>
                            {{ $campaign->currency }} {{ number_format($campaign->raised_amount, 2) }}
                            <div style="margin-top: 0.25rem; background: var(--muted); border-radius: 9999px; height: 4px; width: 80px;">
                                <div style="background: var(--primary); border-radius: 9999px; height: 4px; width: {{ min(100, $campaign->progressPercentage()) }}%;"></div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $statusBadge }}">{{ ucfirst($campaign->status) }}</span>
                        </td>
                        <td style="font-size: 0.875rem; color: var(--muted-foreground);">
                            {{ $campaign->created_at->format('M d, Y') }}
                        </td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('organizer.campaigns.show', $campaign) }}" class="action-btn" title="View">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                @if(in_array($campaign->status, ['draft', 'rejected', 'paused']))
                                    <a href="{{ route('organizer.campaigns.edit', $campaign) }}" class="action-btn" title="Edit">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                @endif
                                @if($campaign->status === 'draft')
                                    <form action="{{ route('organizer.campaigns.destroy', $campaign) }}" method="POST" id="del-c-{{ $campaign->id }}">
                                        @csrf @method('DELETE')
                                        <button type="button" class="action-btn action-btn-danger" title="Delete"
                                            onclick="showDanger('Delete Campaign','Delete this draft campaign? This cannot be undone.').then(c=>{ if(c) document.getElementById('del-c-{{ $campaign->id }}').submit(); })">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
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
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 class="empty-state-title">No campaigns yet</h3>
            <p class="empty-state-description">Create your first fundraising campaign to get started.</p>
            <a href="{{ route('organizer.campaigns.create') }}" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create Campaign
            </a>
        </div>
    @endif
</div>
@endsection
