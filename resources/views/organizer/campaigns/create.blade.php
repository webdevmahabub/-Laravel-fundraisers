@extends('tyro-dashboard::layouts.user')

@section('title', 'Create Campaign')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('organizer.campaigns.index') }}">My Campaigns</a>
<span class="breadcrumb-separator">/</span>
<span>Create</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Create Campaign</h1>
            <p class="page-description">Fill in the details below. Your campaign will be saved as a draft first.</p>
        </div>
    </div>
</div>

<form action="{{ route('organizer.campaigns.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start;">

        {{-- Main content --}}
        <div style="display: flex; flex-direction: column; gap: 1rem;">

            <div class="card">
                <div class="card-header"><h3 class="card-title">Basic Information</h3></div>
                <div class="card-body">
                    <div style="display: grid; gap: 1.25rem;">

                        <div class="form-group">
                            <label for="title" class="form-label">Campaign Title <span style="color: var(--destructive);">*</span></label>
                            <input type="text" name="title" id="title"
                                class="form-input @error('title') is-invalid @enderror"
                                value="{{ old('title') }}"
                                placeholder="E.g. Help Rahim's Medical Treatment"
                                required>
                            @error('title')
                                <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="short_description" class="form-label">Short Description <span style="color: var(--destructive);">*</span></label>
                            <textarea name="short_description" id="short_description" rows="2"
                                class="form-textarea @error('short_description') is-invalid @enderror"
                                placeholder="A brief summary shown in campaign cards (max 500 characters)"
                                maxlength="500"
                                required>{{ old('short_description') }}</textarea>
                            @error('short_description')
                                <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="story" class="form-label">Campaign Story <span style="color: var(--destructive);">*</span></label>
                            <textarea name="story" id="story" rows="10"
                                class="form-textarea @error('story') is-invalid @enderror"
                                placeholder="Tell your story in detail. Explain why you need help, how funds will be used, and any updates you can share. (minimum 100 characters)"
                                required>{{ old('story') }}</textarea>
                            @error('story')
                                <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Cover Image</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="cover_image" class="form-label">Upload Cover Image</label>
                        <input type="file" name="cover_image" id="cover_image"
                            class="form-input @error('cover_image') is-invalid @enderror"
                            accept="image/jpeg,image/png,image/webp">
                        <small class="form-hint">JPG, PNG or WebP — max 5 MB. Recommended: 1200 × 675 px.</small>
                        @error('cover_image')
                            <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar settings --}}
        <div style="display: flex; flex-direction: column; gap: 1rem;">

            <div class="card">
                <div class="card-header"><h3 class="card-title">Goal & Category</h3></div>
                <div class="card-body">
                    <div style="display: grid; gap: 1.25rem;">

                        <div class="form-group">
                            <label for="goal_amount" class="form-label">Fundraising Goal <span style="color: var(--destructive);">*</span></label>
                            <div style="position: relative;">
                                <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--muted-foreground); font-weight: 500;">$</span>
                                <input type="number" name="goal_amount" id="goal_amount"
                                    class="form-input @error('goal_amount') is-invalid @enderror"
                                    style="padding-left: 1.75rem;"
                                    value="{{ old('goal_amount') }}"
                                    min="1" step="0.01"
                                    placeholder="5000.00"
                                    required>
                            </div>
                            @error('goal_amount')
                                <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="category_id" class="form-label">Category</label>
                            <select name="category_id" id="category_id"
                                class="form-select @error('category_id') is-invalid @enderror">
                                <option value="">Select a category...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Campaign Duration</h3></div>
                <div class="card-body">
                    <div style="display: grid; gap: 1.25rem;">

                        <div class="form-group">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date"
                                class="form-input @error('start_date') is-invalid @enderror"
                                value="{{ old('start_date', now()->toDateString()) }}">
                            @error('start_date')
                                <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date"
                                class="form-input @error('end_date') is-invalid @enderror"
                                value="{{ old('end_date') }}">
                            @error('end_date')
                                <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; flex-direction: column;">
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    Save as Draft
                </button>
                <a href="{{ route('organizer.campaigns.index') }}" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                    Cancel
                </a>
            </div>

        </div>
    </div>
</form>
@endsection
