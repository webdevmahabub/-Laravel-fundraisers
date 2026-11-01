@extends('tyro-dashboard::layouts.admin')

@section('title', 'Fee Configuration')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Fee Configuration</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Fee Configuration</h1>
            <p class="page-description">Manage platform, processing, and withdrawal fee rates. Changes apply to new transactions only — historical records are immutable.</p>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 1.25rem;">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('admin.fee-config.update') }}">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h2 class="card-title">Donation Fees</h2>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Setting</th>
                        <th style="width: 220px;">Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($configurations as $config)
                        <tr>
                            <td>
                                <div style="font-weight: 600;">{{ str_replace('_', ' ', ucwords($config->key)) }}</div>
                                <div class="text-muted" style="font-size: 0.8rem;">{{ $config->description }}</div>
                            </td>
                            <td>
                                <input type="number" step="0.0001" min="0"
                                    name="fees[{{ $config->key }}]"
                                    value="{{ old('fees.' . $config->key, $config->value) }}"
                                    class="form-input @error('fees.' . $config->key) input-error @enderror"
                                    style="width: 100%;" />
                                @error('fees.' . $config->key)
                                    <div class="text-danger" style="font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="display: flex; gap: 0.75rem;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('admin.fee-config.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
