@extends('tyro-dashboard::layouts.user')

@section('title', $campaign->title)

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('organizer.campaigns.index') }}">My Campaigns</a>
<span class="breadcrumb-separator">/</span>
<span>{{ Str::limit($campaign->title, 40) }}</span>
@endsection

@section('content')
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

<div class="page-header">
    <div class="page-header-row">
        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <h1 class="page-title" style="margin-bottom: 0;">{{ $campaign->title }}</h1>
            <span class="badge {{ $statusBadge }}" style="font-size: 0.875rem;">{{ ucfirst($campaign->status) }}</span>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if(in_array($campaign->status, ['draft', 'rejected', 'paused']))
                <a href="{{ route('organizer.campaigns.edit', $campaign) }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit
                </a>
            @endif
            @if($campaign->status === 'draft')
                <form action="{{ route('organizer.campaigns.submit', $campaign) }}" method="POST" id="submit-form">
                    @csrf
                    <button type="button" class="btn btn-primary"
                        onclick="showConfirm('Submit for Review','Submit this campaign for admin review? You won\'t be able to edit it while it\'s under review.').then(c=>{ if(c) document.getElementById('submit-form').submit(); })">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Submit for Review
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="grid-2" style="gap: 1.5rem;">

    {{-- Left: details + story --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">

        {{-- Cover image --}}
        @if($campaign->cover_image_path)
        <div class="card" style="padding: 0; overflow: hidden;">
            <img src="{{ Storage::disk('public')->url($campaign->cover_image_path) }}"
                alt="{{ $campaign->title }}"
                style="width: 100%; max-height: 280px; object-fit: cover; display: block;">
        </div>
        @endif

        {{-- Story --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Campaign Story</h3></div>
            <div class="card-body" style="line-height: 1.7; color: var(--muted-foreground); white-space: pre-line;">{{ $campaign->story }}</div>
        </div>

        {{-- Recent Updates --}}
        @if($campaign->updates->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title">Recent Updates</h3></div>
            <div class="card-body" style="padding: 0;">
                @foreach($campaign->updates as $update)
                <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <strong style="font-size: 0.95rem;">{{ $update->title }}</strong>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <small style="color: var(--muted-foreground);">{{ $update->created_at->format('M d, Y') }}</small>
                            <form action="{{ route('organizer.campaigns.updates.destroy', [$campaign, $update]) }}" method="POST" id="del-update-{{ $update->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="action-btn action-btn-danger" title="Delete update"
                                    onclick="showDanger('Delete Update','Remove this update?').then(c=>{ if(c) document.getElementById('del-update-{{ $update->id }}').submit(); })">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    <p style="color: var(--muted-foreground); font-size: 0.875rem; margin: 0; white-space: pre-line;">{{ Str::limit($update->body, 200) }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Post new update (active/paused/completed campaigns only) --}}
        @if(in_array($campaign->status, ['active', 'paused', 'completed']))
        <div class="card">
            <div class="card-header"><h3 class="card-title">Post an Update</h3></div>
            <div class="card-body">
                <form action="{{ route('organizer.campaigns.updates.store', $campaign) }}" method="POST">
                    @csrf
                    <div style="display: grid; gap: 1rem;">
                        <div class="form-group">
                            <label for="update_title" class="form-label">Title <span style="color: var(--destructive);">*</span></label>
                            <input type="text" name="title" id="update_title"
                                class="form-input @error('title') is-invalid @enderror"
                                value="{{ old('title') }}"
                                placeholder="E.g. Treatment is going well!"
                                required>
                            @error('title')
                                <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="update_body" class="form-label">Message <span style="color: var(--destructive);">*</span></label>
                            <textarea name="body" id="update_body" rows="4"
                                class="form-textarea @error('body') is-invalid @enderror"
                                placeholder="Share a progress update with your donors..."
                                required>{{ old('body') }}</textarea>
                            @error('body')
                                <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Post Update</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>

    {{-- Right: stats + meta --}}
    <div style="display: flex; flex-direction: column; gap: 1rem;">

        {{-- Progress card --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Fundraising Progress</h3></div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="font-size: 1.5rem; font-weight: 700;">
                        {{ $campaign->currency }} {{ number_format($campaign->raised_amount, 2) }}
                    </span>
                    <span style="color: var(--muted-foreground); font-size: 0.9rem; align-self: flex-end;">
                        of {{ $campaign->currency }} {{ number_format($campaign->goal_amount, 2) }}
                    </span>
                </div>
                <div style="background: var(--muted); border-radius: 9999px; height: 8px; margin-bottom: 0.5rem;">
                    <div style="background: var(--primary); border-radius: 9999px; height: 8px; width: {{ $campaign->progressPercentage() }}%;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem; color: var(--muted-foreground);">
                    <span>{{ $campaign->progressPercentage() }}% raised</span>
                    @if($campaign->end_date)
                        <span>Ends {{ $campaign->end_date->format('M d, Y') }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Campaign details --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Details</h3></div>
            <div class="card-body">
                <dl style="display: grid; gap: 0.75rem; font-size: 0.875rem;">
                    @if($campaign->category)
                    <div style="display: flex; justify-content: space-between;">
                        <dt style="color: var(--muted-foreground);">Category</dt>
                        <dd><span class="badge badge-secondary">{{ $campaign->category->name }}</span></dd>
                    </div>
                    @endif
                    <div style="display: flex; justify-content: space-between;">
                        <dt style="color: var(--muted-foreground);">Currency</dt>
                        <dd>{{ $campaign->currency }}</dd>
                    </div>
                    @if($campaign->start_date)
                    <div style="display: flex; justify-content: space-between;">
                        <dt style="color: var(--muted-foreground);">Start Date</dt>
                        <dd>{{ $campaign->start_date->format('M d, Y') }}</dd>
                    </div>
                    @endif
                    @if($campaign->end_date)
                    <div style="display: flex; justify-content: space-between;">
                        <dt style="color: var(--muted-foreground);">End Date</dt>
                        <dd>{{ $campaign->end_date->format('M d, Y') }}</dd>
                    </div>
                    @endif
                    <div style="display: flex; justify-content: space-between;">
                        <dt style="color: var(--muted-foreground);">Created</dt>
                        <dd>{{ $campaign->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <dt style="color: var(--muted-foreground);">Slug</dt>
                        <dd style="font-family: monospace; font-size: 0.8rem;">{{ $campaign->slug }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Danger zone for drafts --}}
        @if($campaign->status === 'draft')
        <div class="card" style="border-color: var(--destructive);">
            <div class="card-header">
                <h3 class="card-title" style="color: var(--destructive);">Danger Zone</h3>
            </div>
            <div class="card-body">
                <p style="font-size: 0.875rem; color: var(--muted-foreground); margin-bottom: 1rem;">
                    Permanently delete this draft campaign. This action cannot be undone.
                </p>
                <form action="{{ route('organizer.campaigns.destroy', $campaign) }}" method="POST" id="delete-campaign-form">
                    @csrf @method('DELETE')
                    <button type="button" class="btn btn-destructive"
                        onclick="showDanger('Delete Campaign','Delete this draft campaign? This action cannot be undone.').then(c=>{ if(c) document.getElementById('delete-campaign-form').submit(); })">
                        Delete Campaign
                    </button>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
