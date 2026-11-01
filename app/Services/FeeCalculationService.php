<?php

namespace App\Services;

use App\Models\FeeConfiguration;

/**
 * Reads fee rates from the database and calculates the breakdown for a donation.
 *
 * All rates are snapshots — callers must store the returned amounts on the
 * donation record so historical records are immutable when admins change rates.
 */
class FeeCalculationService
{
    /**
     * Calculate the full fee breakdown for a given gross donation amount.
     *
     * Returns an array with:
     *   - gross_amount          float  The donor's input amount
     *   - platform_fee_pct      float  Platform fee % in effect at this moment
     *   - platform_fee_amount   float  Absolute platform fee deducted
     *   - processing_fee_pct    float  Payment processor % in effect
     *   - processing_fee_flat   float  Payment processor flat fee in effect
     *   - payment_processing_fee float Combined processor fee
     *   - total_fees            float  All fees combined
     *   - net_amount            float  Amount the organizer receives
     *
     * @return array{
     *   gross_amount: float,
     *   platform_fee_pct: float,
     *   platform_fee_amount: float,
     *   processing_fee_pct: float,
     *   processing_fee_flat: float,
     *   payment_processing_fee: float,
     *   total_fees: float,
     *   net_amount: float
     * }
     */
    public function calculate(float $grossAmount): array
    {
        // Read current rates — these are snapshotted into the donation record,
        // so changing them later never affects historical transactions.
        $platformFeePct = FeeConfiguration::getValue('platform_fee_pct', 5.0);
        $processingPct = FeeConfiguration::getValue('payment_processing_fee_pct', 2.9);
        $processingFlat = FeeConfiguration::getValue('payment_processing_fee_flat', 0.30);

        $platformFeeAmount = round($grossAmount * ($platformFeePct / 100), 2);
        $paymentProcessingFee = round(
            ($grossAmount * ($processingPct / 100)) + $processingFlat,
            2
        );

        $totalFees = $platformFeeAmount + $paymentProcessingFee;
        $netAmount = round($grossAmount - $totalFees, 2);

        return [
            'gross_amount' => $grossAmount,
            'platform_fee_pct' => $platformFeePct,
            'platform_fee_amount' => $platformFeeAmount,
            'processing_fee_pct' => $processingPct,
            'processing_fee_flat' => $processingFlat,
            'payment_processing_fee' => $paymentProcessingFee,
            'total_fees' => $totalFees,
            'net_amount' => max(0.0, $netAmount),
        ];
    }

    /**
     * Return the minimum donation amount from configuration.
     */
    public function minimumDonation(): float
    {
        return FeeConfiguration::getValue('minimum_donation_amount', 1.0);
    }

    /**
     * Current withdrawal fee percentage in effect.
     */
    public function withdrawalFeePct(): float
    {
        return FeeConfiguration::getValue('withdrawal_fee_pct', 1.0);
    }

    /**
     * Calculate the withdrawal fee breakdown for a requested gross amount.
     *
     * Returns a snapshot (gross, fee %, fee amount, net) so the values can be
     * persisted on the withdrawal record and stay immutable when rates change.
     *
     * @return array{
     *   gross_amount: float,
     *   withdrawal_fee_pct: float,
     *   withdrawal_fee_amount: float,
     *   net_amount: float
     * }
     */
    public function calculateWithdrawalFee(float $amount): array
    {
        $pct = $this->withdrawalFeePct();
        $feeAmount = round($amount * ($pct / 100), 2);
        $netAmount = round($amount - $feeAmount, 2);

        return [
            'gross_amount' => $amount,
            'withdrawal_fee_pct' => $pct,
            'withdrawal_fee_amount' => $feeAmount,
            'net_amount' => max(0.0, $netAmount),
        ];
    }

    /**
     * Return the minimum withdrawal amount from configuration.
     */
    public function minimumWithdrawal(): float
    {
        return FeeConfiguration::getValue('minimum_withdrawal_amount', 10.0);
    }
}
