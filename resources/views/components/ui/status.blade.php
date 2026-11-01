@props(['status' => null])

@php
    $key = strtolower((string) $status);

    $tones = [
        'active' => 'active',
        'completed' => 'completed',
        'approved' => 'approved',
        'paid' => 'paid',
        'draft' => 'draft',
        'pending' => 'pending',
        'paused' => 'paused',
        'processing' => 'processing',
        'failed' => 'failed',
        'rejected' => 'rejected',
        'cancelled' => 'cancelled',
        'suspended' => 'suspended',
    ];

    $labels = [
        'active' => 'Active',
        'completed' => 'Completed',
        'approved' => 'Approved',
        'paid' => 'Paid',
        'draft' => 'Draft',
        'pending' => 'Pending',
        'paused' => 'Paused',
        'processing' => 'Processing',
        'failed' => 'Failed',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
        'suspended' => 'Suspended',
    ];

    $tone = $tones[$key] ?? 'pending';
    $label = $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
@endphp

<span {{ $attributes->merge(['class' => 'status status--' . $tone]) }}>{{ $label }}</span>
