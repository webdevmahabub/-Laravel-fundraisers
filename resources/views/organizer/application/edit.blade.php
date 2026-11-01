@extends('tyro-dashboard::layouts.user')

@section('title', 'Edit Application')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('organizer.application.show') }}">Organizer Application</a>
<span class="breadcrumb-separator">/</span>
<span>Edit</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Edit Application</h1>
            <p class="page-description">Update your organizer application details.</p>
        </div>
    </div>
</div>

<div class="card" style="max-width: 720px;">
    <div class="card-body">
        <form action="{{ route('organizer.application.update') }}" method="POST">
            @csrf
            @method('PATCH')

            <div style="display: grid; gap: 1.25rem;">
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name <span style="color: var(--destructive);">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-input @error('full_name') is-invalid @enderror"
                        value="{{ old('full_name', $profile->full_name) }}" required>
                    @error('full_name')
                        <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="form-input @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $profile->phone) }}" placeholder="+1 234 567 8900">
                        @error('phone')
                            <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="national_id" class="form-label">National ID / Passport No.</label>
                        <input type="text" name="national_id" id="national_id" class="form-input @error('national_id') is-invalid @enderror"
                            value="{{ old('national_id', $profile->national_id) }}">
                        @error('national_id')
                            <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="address" class="form-label">Address</label>
                    <textarea name="address" id="address" class="form-textarea @error('address') is-invalid @enderror"
                        rows="2">{{ old('address', $profile->address) }}</textarea>
                    @error('address')
                        <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="bio" class="form-label">About You / Bio</label>
                    <textarea name="bio" id="bio" class="form-textarea @error('bio') is-invalid @enderror"
                        rows="3">{{ old('bio', $profile->bio) }}</textarea>
                    @error('bio')
                        <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="website" class="form-label">Website <span class="form-label-optional">(optional)</span></label>
                    <input type="url" name="website" id="website" class="form-input @error('website') is-invalid @enderror"
                        value="{{ old('website', $profile->website) }}" placeholder="https://yourwebsite.com">
                    @error('website')
                        <span class="form-hint" style="color: var(--destructive);">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border); display: flex; gap: 0.75rem;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('organizer.application.show') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
