@extends('tyro-dashboard::layouts.user')

@section('title', 'Notifications')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Notifications</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Notifications</h1>
            @if($unreadCount > 0)
                <p class="page-description">You have {{ $unreadCount }} unread {{ Str::plural('notification', $unreadCount) }}.</p>
            @else
                <p class="page-description">You're all caught up.</p>
            @endif
        </div>
        @if($unreadCount > 0)
            <form action="{{ route('notifications.markAllRead') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Mark All Read
                </button>
            </form>
        @endif
    </div>
</div>

<div class="card">
    @if($notifications->count())
        <div style="padding: 0;">
            @foreach($notifications as $notification)
            @php
                $isUnread = is_null($notification->read_at);
                $data     = $notification->data;
                $type     = $data['type'] ?? '';

                $iconMap = [
                    'campaign_approved'               => ['color' => 'var(--success)',     'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                    'campaign_rejected'               => ['color' => 'var(--destructive)', 'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                    'campaign_submitted_for_review'   => ['color' => 'var(--warning)',     'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />'],
                    'organizer_application_approved'  => ['color' => 'var(--success)',     'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />'],
                    'organizer_application_rejected'  => ['color' => 'var(--destructive)', 'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />'],
                    'organizer_application_submitted' => ['color' => 'var(--primary)',     'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />'],
                    'donation_received'               => ['color' => 'var(--success)',     'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />'],
                    'withdrawal_completed'            => ['color' => 'var(--success)',     'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />'],
                    'withdrawal_failed'               => ['color' => 'var(--destructive)', 'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />'],
                ];
                $icon = $iconMap[$type] ?? ['color' => 'var(--primary)', 'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />'];
            @endphp
            <div style="
                display: flex;
                align-items: flex-start;
                gap: 1rem;
                padding: 1rem 1.25rem;
                border-bottom: 1px solid var(--border);
                background: {{ $isUnread ? 'color-mix(in srgb, var(--primary) 5%, var(--background))' : 'transparent' }};
                transition: background 0.15s;
            ">
                {{-- Icon --}}
                <div style="
                    width: 2.25rem;
                    height: 2.25rem;
                    border-radius: 50%;
                    background: color-mix(in srgb, {{ $icon['color'] }} 15%, transparent);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    margin-top: 0.1rem;
                ">
                    <svg style="width: 1.1rem; height: 1.1rem; color: {{ $icon['color'] }};"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        {!! $icon['svg'] !!}
                    </svg>
                </div>

                {{-- Content --}}
                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; flex-wrap: wrap;">
                        <span style="font-weight: {{ $isUnread ? '600' : '500' }}; font-size: 0.9rem;">
                            {{ $data['title'] ?? 'Notification' }}
                        </span>
                        <span style="font-size: 0.75rem; color: var(--muted-foreground); white-space: nowrap; flex-shrink: 0;">
                            {{ $notification->created_at->diffForHumans() }}
                        </span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--muted-foreground); margin: 0.25rem 0 0; line-height: 1.5;">
                        {{ $data['body'] ?? '' }}
                    </p>
                </div>

                {{-- Mark read action --}}
                <form action="{{ route('notifications.markRead', $notification->id) }}" method="POST" style="flex-shrink: 0;">
                    @csrf
                    <button type="submit"
                        title="{{ $isUnread ? 'Mark as read' : 'Already read' }}"
                        style="
                            width: 0.625rem;
                            height: 0.625rem;
                            border-radius: 50%;
                            background: {{ $isUnread ? 'var(--primary)' : 'var(--border)' }};
                            border: none;
                            cursor: {{ $isUnread ? 'pointer' : 'default' }};
                            padding: 0;
                            display: block;
                            margin-top: 0.5rem;
                        "
                        {{ !$isUnread ? 'disabled' : '' }}>
                    </button>
                </form>
            </div>
            @endforeach
        </div>

        @if($notifications->hasPages())
            <div class="pagination">
                {{ $notifications->links() }}
            </div>
        @endif
    @else
        <div class="empty-state">
            <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <h3 class="empty-state-title">No notifications</h3>
            <p class="empty-state-description">You'll see important updates here as they happen.</p>
        </div>
    @endif
</div>
@endsection
