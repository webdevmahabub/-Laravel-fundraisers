@extends('tyro-dashboard::layouts.admin')

@section('title', 'Organizer Verification')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Organizer Verification</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Organizer Verification</h1>
            <p class="page-description">Review and process organizer applications.</p>
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
            <div class="stat-label">Under Review</div>
            <div class="stat-value">{{ $counts['under_review'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Approved</div>
            <div class="stat-value">{{ $counts['approved'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Rejected</div>
            <div class="stat-value">{{ $counts['rejected'] }}</div>
        </div>
    </div>
</div>

{{-- Status filter tabs --}}
<div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
    @foreach(['pending' => 'Pending', 'under_review' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
        <a href="{{ route('admin.organizer-verification.index', ['status' => $key]) }}"
            class="btn {{ $status === $key ? 'btn-primary' : 'btn-secondary' }}"
            style="font-size: 0.875rem; padding: 0.375rem 0.875rem;">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="card">
    @if($applications->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Full Name</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($applications as $application)
                    @php
                        $statusBadge = [
                            'pending'      => 'badge-warning',
                            'under_review' => 'badge-primary',
                            'approved'     => 'badge-success',
                            'rejected'     => 'badge-danger',
                            'suspended'    => 'badge-danger',
                        ][$application->status] ?? 'badge-secondary';
                    @endphp
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-cell-avatar">
                                    {{ strtoupper(substr($application->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="user-cell-info">
                                    <div class="user-cell-name">{{ $application->user->name }}</div>
                                    <div class="user-cell-email">{{ $application->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $application->full_name }}</td>
                        <td>
                            <span class="badge {{ $statusBadge }}">
                                {{ str_replace('_', ' ', ucfirst($application->status)) }}
                            </span>
                        </td>
                        <td style="font-size: 0.875rem;">{{ $application->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="action-buttons" style="justify-content: flex-end;">
                                <a href="{{ route('admin.organizer-verification.show', $application) }}"
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

        @if($applications->hasPages())
            <div class="pagination">
                {{ $applications->links() }}
            </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="empty-state-title">No applications found</h3>
            <p class="empty-state-description">There are no organizer applications with the selected status.</p>
        </div>
    @endif
</div>
@endsection
