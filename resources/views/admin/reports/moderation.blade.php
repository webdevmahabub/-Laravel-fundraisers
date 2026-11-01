@extends('tyro-dashboard::layouts.admin')

@section('title', 'User Reports')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>User Reports</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">User Reports</h1>
            <p class="page-description">Review content reported by users for fraud, spam, or inappropriate activity.</p>
        </div>
    </div>
</div>

{{-- Counts --}}
<div class="stats-grid" style="margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Open</div>
            <div class="stat-value">{{ $counts['open'] }}</div>
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
            <div class="stat-label">Resolved</div>
            <div class="stat-value">{{ $counts['resolved'] }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-label">Dismissed</div>
            <div class="stat-value">{{ $counts['dismissed'] }}</div>
        </div>
    </div>
</div>

{{-- Status filter tabs --}}
<div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
    @foreach(['open' => 'Open', 'under_review' => 'Under Review', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed', 'all' => 'All'] as $key => $label)
        <a href="{{ route('admin.user-reports.index', ['status' => $key]) }}"
            class="btn {{ $status === $key ? 'btn-primary' : 'btn-secondary' }}"
            style="font-size: 0.875rem; padding: 0.375rem 0.875rem;">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="card">
    @if($reports->count())
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reported Content</th>
                        <th>Reason</th>
                        <th>Reporter</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reports as $report)
                    @php
                        $statusBadge = [
                            'open'         => 'badge-warning',
                            'under_review' => 'badge-primary',
                            'resolved'     => 'badge-success',
                            'dismissed'    => 'badge-secondary',
                        ][$report->status] ?? 'badge-secondary';

                        $reportableLabel = match (true) {
                            $report->reportable instanceof \App\Models\Campaign  => 'Campaign: ' . Str::limit($report->reportable->title, 40),
                            $report->reportable instanceof \App\Models\Donation   => 'Donation #' . $report->reportable->id,
                            default                                               => ucfirst(class_basename($report->reportable_type ?? 'Unknown')) . ' #' . $report->reportable_id,
                        };
                    @endphp
                    <tr>
                        <td>
                            <div style="font-size: 0.875rem; font-weight: 500;">{{ $reportableLabel }}</div>
                        </td>
                        <td>
                            <span class="badge badge-secondary" style="text-transform: capitalize;">
                                {{ str_replace('_', ' ', $report->reason) }}
                            </span>
                        </td>
                        <td style="font-size: 0.875rem;">{{ $report->reporter->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $statusBadge }}">
                                {{ str_replace('_', ' ', ucfirst($report->status)) }}
                            </span>
                        </td>
                        <td style="font-size: 0.875rem; color: var(--muted-foreground);">
                            {{ $report->created_at->format('M d, Y') }}
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('admin.user-reports.show', $report) }}"
                                class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.75rem;">
                                Review
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($reports->hasPages())
            <div class="pagination">
                {{ $reports->links() }}
            </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="empty-state-title">No reports found</h3>
            <p class="empty-state-description">No user reports match the selected status.</p>
        </div>
    @endif
</div>
@endsection
