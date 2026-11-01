@extends('tyro-dashboard::layouts.admin')

@section('title', 'Add Payment Method')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<a href="{{ route('admin.payment-methods.index') }}">Payment Methods</a>
<span class="breadcrumb-separator">/</span>
<span>Add New</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Add Payment Method</h1>
            <p class="page-description">Create a new payment method for donors.</p>
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

<form method="POST" action="{{ route('admin.payment-methods.store') }}">
    @csrf

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h2 class="card-title">Payment Method Details</h2>
        </div>
        <div style="padding: 1.5rem; display: grid; gap: 1.25rem; max-width: 600px;">

            <div class="form-group">
                <label class="form-label" for="name">Name <span style="color: var(--color-danger);">*</span></label>
                <input type="text" name="name" id="name" class="form-input @error('name') is-invalid @enderror"
                    value="{{ old('name') }}" placeholder="e.g. bKash, Nagad, Wise, Stripe" required>
                @error('name')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="currency_type">Currency Type <span style="color: var(--color-danger);">*</span></label>
                <select name="currency_type" id="currency_type" class="form-select @error('currency_type') is-invalid @enderror" required>
                    <option value="">Select currency type...</option>
                    <option value="BDT" {{ old('currency_type') === 'BDT' ? 'selected' : '' }}>🇧🇩 BDT (Bangladeshi Taka)</option>
                    <option value="USD" {{ old('currency_type') === 'USD' ? 'selected' : '' }}>🇺🇸 USD (US Dollar)</option>
                </select>
                @error('currency_type')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
                <span class="form-hint">BDT methods: bKash, Nagad, Bank. USD methods: Wise, Stripe, PayPal, etc.</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="icon">Icon <span class="form-label-optional">(emoji)</span></label>
                <input type="text" name="icon" id="icon" class="form-input @error('icon') is-invalid @enderror"
                    value="{{ old('icon') }}" placeholder="e.g. 📱, 🏦, 💳" maxlength="10">
                @error('icon')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
                <span class="form-hint">An emoji to display next to the payment method name.</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span class="form-label-optional">(optional)</span></label>
                <textarea name="description" id="description" class="form-textarea @error('description') is-invalid @enderror"
                    rows="2" placeholder="Brief description for donors...">{{ old('description') }}</textarea>
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
                    rows="3" placeholder="e.g. Send money to the number below and enter the transaction ID...">{{ old('payment_instructions') }}</textarea>
                @error('payment_instructions')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
                <span class="form-hint">Step-by-step instructions donors should follow.</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="account_holder_name">Account Holder Name</label>
                <input type="text" name="account_holder_name" id="account_holder_name" class="form-input @error('account_holder_name') is-invalid @enderror"
                    value="{{ old('account_holder_name') }}" placeholder="e.g. John Doe">
                @error('account_holder_name')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="account_number">Account / Phone Number</label>
                <input type="text" name="account_number" id="account_number" class="form-input @error('account_number') is-invalid @enderror"
                    value="{{ old('account_number') }}" placeholder="e.g. 01XXXXXXXXX">
                @error('account_number')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="bank_name">Bank Name</label>
                <input type="text" name="bank_name" id="bank_name" class="form-input @error('bank_name') is-invalid @enderror"
                    value="{{ old('bank_name') }}" placeholder="e.g. Dutch-Bangla Bank">
                @error('bank_name')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="branch_name">Branch Name</label>
                <input type="text" name="branch_name" id="branch_name" class="form-input @error('branch_name') is-invalid @enderror"
                    value="{{ old('branch_name') }}" placeholder="e.g. Dhanmondi Branch">
                @error('branch_name')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="routing_number">Routing / Swift Number</label>
                <input type="text" name="routing_number" id="routing_number" class="form-input @error('routing_number') is-invalid @enderror"
                    value="{{ old('routing_number') }}" placeholder="e.g. 090201203">
                @error('routing_number')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="phone_number">Phone / Contact Number</label>
                <input type="text" name="phone_number" id="phone_number" class="form-input @error('phone_number') is-invalid @enderror"
                    value="{{ old('phone_number') }}" placeholder="e.g. +880 1XXXXXXXXX">
                @error('phone_number')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="qr_code_image_path">QR Code Image Path <span class="form-label-optional">(optional)</span></label>
                <input type="text" name="qr_code_image_path" id="qr_code_image_path" class="form-input @error('qr_code_image_path') is-invalid @enderror"
                    value="{{ old('qr_code_image_path') }}" placeholder="e.g. payment-qr/bkash.png">
                @error('qr_code_image_path')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
                <span class="form-hint">Path to QR code image in storage for donors to scan.</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="sort_order">Display Order</label>
                <input type="number" name="sort_order" id="sort_order" class="form-input @error('sort_order') is-invalid @enderror"
                    value="{{ old('sort_order', 0) }}" min="0">
                @error('sort_order')
                    <span class="form-hint error">{{ $message }}</span>
                @enderror
                <span class="form-hint">Lower numbers appear first. Default: 0</span>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                    <span class="form-label" style="margin-bottom: 0;">Active (visible to donors)</span>
                </label>
            </div>

        </div>
    </div>

    <div style="display: flex; gap: 0.75rem;">
        <button type="submit" class="btn btn-primary">Create Payment Method</button>
        <a href="{{ route('admin.payment-methods.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
