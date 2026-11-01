@props([
    'value' => 0,
    'currency' => 'USD',
    'decimals' => 0,
    'class' => '',
])

@php
    $amount = (float) $value;

    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'BDT' => '৳',
        'INR' => '₹',
        'PKR' => '₨',
        'NPR' => '₨',
        'JPY' => '¥',
    ];

    $code = strtoupper((string) $currency);
    $symbol = $symbols[$code] ?? null;

    $number = number_format($amount, (int) $decimals);
    $formatted = $symbol ? $symbol . $number : $code . ' ' . $number;
@endphp

<span {{ $attributes->merge(['class' => 'money ' . $class]) }}>{{ $formatted }}</span>
