<?php

/**
 * Unit tests for FeeCalculationService.
 *
 * These tests require a booted Laravel application (DB reads for fee config),
 * so they extend TestCase and use DatabaseMigrations just like Feature tests.
 */

use App\Models\FeeConfiguration;
use App\Services\FeeCalculationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

// ─── Helpers ────────────────────────────────────────────────────────────────

function setFees(
    float $platformPct = 5.0,
    float $processingPct = 2.9,
    float $processingFlat = 0.30,
    float $withdrawalPct = 1.0,
    float $minimumDonation = 1.0,
    float $minimumWithdrawal = 10.0
): void {
    $rows = [
        ['key' => 'platform_fee_pct',            'value' => $platformPct],
        ['key' => 'payment_processing_fee_pct',  'value' => $processingPct],
        ['key' => 'payment_processing_fee_flat', 'value' => $processingFlat],
        ['key' => 'withdrawal_fee_pct',          'value' => $withdrawalPct],
        ['key' => 'minimum_donation_amount',     'value' => $minimumDonation],
        ['key' => 'minimum_withdrawal_amount',   'value' => $minimumWithdrawal],
    ];
    foreach ($rows as $row) {
        FeeConfiguration::updateOrCreate(['key' => $row['key']], array_merge($row, ['description' => 'test', 'is_active' => true]));
    }
}

function calc(): FeeCalculationService
{
    return app(FeeCalculationService::class);
}

// ─── Donation fee math ───────────────────────────────────────────────────────

test('calculates correct platform fee for a 100 USD donation', function () {
    setFees();
    $result = calc()->calculate(100.0);

    expect($result['platform_fee_pct'])->toBe(5.0);
    expect($result['platform_fee_amount'])->toBe(5.0);
});

test('calculates correct payment processing fee for a 100 USD donation', function () {
    setFees();
    $result = calc()->calculate(100.0);
    // 100 * 0.029 + 0.30 = 3.20
    expect($result['payment_processing_fee'])->toBe(3.20);
});

test('net_amount equals gross minus all fees', function () {
    setFees();
    $result = calc()->calculate(100.0);
    $expectedNet = 100.0 - $result['platform_fee_amount'] - $result['payment_processing_fee'];
    expect($result['net_amount'])->toBe(round($expectedNet, 2));
});

test('net_amount is never negative even with extreme fee rates', function () {
    setFees(platformPct: 99.0, processingPct: 99.0, processingFlat: 99.0);
    $result = calc()->calculate(1.0);
    expect($result['net_amount'])->toBeGreaterThanOrEqual(0.0);
});

test('fee amounts sum to gross_amount', function () {
    setFees();
    $result = calc()->calculate(9.99);
    expect(round($result['net_amount'] + $result['total_fees'], 4))->toBe($result['gross_amount']);
});

// ─── Withdrawal fee math ──────────────────────────────────────────────────────

test('withdrawal fee is calculated correctly at 1%', function () {
    setFees(withdrawalPct: 1.0);
    $result = calc()->calculateWithdrawalFee(200.0);
    expect($result['withdrawal_fee_amount'])->toBe(2.0);
    expect($result['net_amount'])->toBe(198.0);
});

test('withdrawal net_amount is never negative', function () {
    setFees(withdrawalPct: 100.0);
    $result = calc()->calculateWithdrawalFee(10.0);
    expect($result['net_amount'])->toBeGreaterThanOrEqual(0.0);
});

// ─── Config reads ─────────────────────────────────────────────────────────────

test('minimum donation reads from database config', function () {
    setFees(minimumDonation: 5.0);
    expect(calc()->minimumDonation())->toBe(5.0);
});

test('minimum withdrawal reads from database config', function () {
    setFees(minimumWithdrawal: 25.0);
    expect(calc()->minimumWithdrawal())->toBe(25.0);
});

test('withdrawal fee percentage reads from database config', function () {
    setFees(withdrawalPct: 2.5);
    expect(calc()->withdrawalFeePct())->toBe(2.5);
});

// ─── Snapshot immutability ────────────────────────────────────────────────────

test('snapshot captures rates at call time regardless of later changes', function () {
    setFees(platformPct: 5.0);
    $before = calc()->calculate(100.0);

    FeeConfiguration::where('key', 'platform_fee_pct')->update(['value' => 20.0]);

    // Original snapshot is unaffected (it is a plain PHP array)
    expect($before['platform_fee_pct'])->toBe(5.0);
    expect($before['platform_fee_amount'])->toBe(5.0);

    // Fresh call uses the new rate
    $after = calc()->calculate(100.0);
    expect($after['platform_fee_pct'])->toBe(20.0);
    expect($after['platform_fee_amount'])->toBe(20.0);
});
