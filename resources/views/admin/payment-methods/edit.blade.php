@extends('tyro-dashboard::layouts.admin')

@section('title', 'Edit Payment Method')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('admin.payment-methods.index') }}">Payment Methods</a>
<span class="breadcrumb-separator">/</span>
<span>Edit {{ $paymentMethod->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Edit Payment Method</h1>
            <p class="page-description">Update "{{ $paymentMethod->name }}" settings.</p>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-error" style="margin-bottom: 1.25rem;">
        <ul style="margin: 0; padding-left: 1.25rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.payment-methods.update', $paymentMethod) }}">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h2 class="card-title">Payment Method Details</h2>
        </div>
        <div style="padding: 1.5rem; display: grid; gap: 1.25rem; max-width: 600px;">

            <div class="form-group">
                <label class="form-label" for="name">Name <span style="color: var(--color-danger);">*</span></label>
                <input type="text" name="name" id="name" class="form-input @error('name') is-invalid @enderror"
                    value="{{ old('name', $paymentMethod->name) }}" required>
                @error('name')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="currency_type">Currency Type <span style="color: var(--color-danger);">*</span></label>
                <select name="currency_type" id="currency_type" class="form-select @error('currency_type') is-invalid @enderror" required>
                    <option value="BDT" {{ old('currency_type', $paymentMethod->currency_type) === 'BDT' ? 'selected' : '' }}>🇧🇩 BDT (Bangladeshi Taka)</option>
                    <option value="USD" {{ old('currency_type', $paymentMethod->currency_type) === 'USD' ? 'selected' : '' }}>🇺🇸 USD (US Dollar)</option>
                </select>
                @error('currency_type')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="icon">Icon <span class="form-label-optional">(emoji)</span></label>
                <input type="text" name="icon" id="icon" class="form-input @error('icon') is-invalid @enderror"
                    value="{{ old('icon', $paymentMethod->icon) }}" maxlength="10">
                @error('icon')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span class="form-label-optional">(optional)</span></label>
                <textarea name="description" id="description" class="form-textarea @error('description') is-invalid @enderror"
                    rows="2">{{ old('description', $paymentMethod->description) }}</textarea>
                @error('description')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div style="border-top: 1px solid var(--color-border); padding-top: 1.25rem; margin-top: 0.5rem;">
                <h3 style="font-size: 0.9375rem; font-weight: 600; margin-bottom: 1rem; color: var(--color-text);">Payment Instructions</h3>
                <p style="font-size: 0.8125rem; color: var(--color-text-muted); margin-bottom: 1rem;">These details are shown to donors after they select this payment method.</p>
            </div>

            <div class="form-group">
                <label class="form-label" for="payment_instructions">Instructions for Donors</label>
                <textarea name="payment_instructions" id="payment_instructions" class="form-textarea @error('payment_instructions') is-invalid @enderror"
                    rows="3" placeholder="e.g. Send money to the number below...">{{ old('payment_instructions', $paymentMethod->payment_instructions) }}</textarea>
                @error('payment_instructions')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="account_holder_name">Account Holder Name</label>
                <input type="text" name="account_holder_name" id="account_holder_name" class="form-input @error('account_holder_name') is-invalid @enderror"
                    value="{{ old('account_holder_name', $paymentMethod->account_holder_name) }}">
                @error('account_holder_name')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="account_number">Account / Phone Number</label>
                <input type="text" name="account_number" id="account_number" class="form-input @error('account_number') is-invalid @enderror"
                    value="{{ old('account_number', $paymentMethod->account_number) }}">
                @error('account_number')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="bank_name">Bank Name</label>
                <input type="text" name="bank_name" id="bank_name" class="form-input @error('bank_name') is-invalid @enderror"
                    value="{{ old('bank_name', $paymentMethod->bank_name) }}">
                @error('bank_name')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="branch_name">Branch Name</label>
                <input type="text" name="branch_name" id="branch_name" class="form-input @error('branch_name') is-invalid @enderror"
                    value="{{ old('branch_name', $paymentMethod->branch_name) }}">
                @error('branch_name')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="routing_number">Routing / Swift Number</label>
                <input type="text" name="routing_number" id="routing_number" class="form-input @error('routing_number') is-invalid @enderror"
                    value="{{ old('routing_number', $paymentMethod->routing_number) }}">
                @error('routing_number')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="phone_number">Phone / Contact Number</label>
                <input type="text" name="phone_number" id="phone_number" class="form-input @error('phone_number') is-invalid @enderror"
                    value="{{ old('phone_number', $paymentMethod->phone_number) }}">
                @error('phone_number')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="qr_code_image_path">QR Code Image Path</label>
                <input type="text" name="qr_code_image_path" id="qr_code_image_path" class="form-input @error('qr_code_image_path') is-invalid @enderror"
                    value="{{ old('qr_code_image_path', $paymentMethod->qr_code_image_path) }}">
                @error('qr_code_image_path')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="sort_order">Display Order</label>
                <input type="number" name="sort_order" id="sort_order" class="form-input @error('sort_order') is-invalid @enderror"
                    value="{{ old('sort_order', $paymentMethod->sort_order) }}" min="0">
                @error('sort_order')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $paymentMethod->is_active) ? 'checked' : '' }}>
                    <span class="form-label" style="margin-bottom: 0;">Active (visible to donors)</span>
                </label>
            </div>

        </div>
    </div>

    <div style="display: flex; gap: 0.75rem;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('admin.payment-methods.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
