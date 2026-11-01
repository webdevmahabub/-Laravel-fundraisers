@extends('tyro-dashboard::layouts.admin')

@section('title', 'Payment Methods')

@section('breadcrumb')
<a href="{{ route($dashboardRoute::name('index')) }}">Dashboard</a>
<span class="breadcrumb-separator">/</span>
<span>Payment Methods</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1 class="page-title">Payment Methods</h1>
            <p class="page-description">Manage payment methods for donations. BDT methods (bKash, Nagad, Bank) and USD methods (Wise, Stripe, etc.) are shown to donors based on campaign currency.</p>
        </div>
        <div>
            <a href="{{ route('admin.payment-methods.create') }}" class="btn btn-primary">+ Add Payment Method</a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 1.25rem;">{{ session('success') }}</div>
@endif

@php
    $bdtMethods = $paymentMethods->where('currency_type', 'BDT');
    $usdMethods = $paymentMethods->where('currency_type', 'USD');
@endphp

{{-- BDT Methods --}}
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">🇧🇩 BDT Payment Methods</h2>
        <span class="badge badge-blue">{{ $bdtMethods->count() }} methods</span>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Icon</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th style="width: 200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bdtMethods as $method)
                    <tr>
                        <td>{{ $method->sort_order }}</td>
                        <td style="font-size: 1.5rem;">{{ $method->icon ?: '💳' }}</td>
                        <td>
                            <div style="font-weight: 600;">{{ $method->name }}</div>
                        </td>
                        <td><code style="font-size: 0.8rem;">{{ $method->slug }}</code></td>
                        <td>
                            <span class="text-muted" style="font-size: 0.8rem;">{{ Str::limit($method->description, 60) ?: '—' }}</span>
                        </td>
                        <td>
                            @if($method->is_active)
                                <span class="badge badge-green">Active</span>
                            @else
                                <span class="badge badge-gray">Disabled</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <form method="POST" action="{{ route('admin.payment-methods.toggle', $method) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $method->is_active ? 'btn-secondary' : 'btn-primary' }}">
                                        {{ $method->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                                <a href="{{ route('admin.payment-methods.edit', $method) }}" class="btn btn-sm btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.payment-methods.destroy', $method) }}" style="display:inline;"
                                    onsubmit="return confirm('Delete {{ $method->name }}? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--color-text-secondary);">
                            No BDT payment methods configured yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- USD Methods --}}
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h2 class="card-title">🇺🇸 USD Payment Methods</h2>
        <span class="badge badge-blue">{{ $usdMethods->count() }} methods</span>
    </div>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Icon</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th style="width: 200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usdMethods as $method)
                    <tr>
                        <td>{{ $method->sort_order }}</td>
                        <td style="font-size: 1.5rem;">{{ $method->icon ?: '💳' }}</td>
                        <td>
                            <div style="font-weight: 600;">{{ $method->name }}</div>
                        </td>
                        <td><code style="font-size: 0.8rem;">{{ $method->slug }}</code></td>
                        <td>
                            <span class="text-muted" style="font-size: 0.8rem;">{{ Str::limit($method->description, 60) ?: '—' }}</span>
                        </td>
                        <td>
                            @if($method->is_active)
                                <span class="badge badge-green">Active</span>
                            @else
                                <span class="badge badge-gray">Disabled</span>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <form method="POST" action="{{ route('admin.payment-methods.toggle', $method) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $method->is_active ? 'btn-secondary' : 'btn-primary' }}">
                                        {{ $method->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                                <a href="{{ route('admin.payment-methods.edit', $method) }}" class="btn btn-sm btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.payment-methods.destroy', $method) }}" style="display:inline;"
                                    onsubmit="return confirm('Delete {{ $method->name }}? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--color-text-secondary);">
                            No USD payment methods configured yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
