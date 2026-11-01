<?php

namespace Database\Seeders;

use App\Models\FeeConfiguration;
use Illuminate\Database\Seeder;

class FeeConfigurationSeeder extends Seeder
{
    /**
     * Default platform fee configurations.
     *
     * These are the baseline rates. Admins can update them through the
     * admin dashboard without touching code. Historical transactions always
     * store the fee values that were in effect at the time — changing these
     * records does NOT retroactively alter existing donations or withdrawals.
     */
    public function run(): void
    {
        $fees = [
            [
                'key' => 'platform_fee_pct',
                'value' => 5.0000,
                'description' => 'Platform fee percentage deducted from each donation (e.g. 5 = 5%).',
                'is_active' => true,
            ],
            [
                'key' => 'payment_processing_fee_pct',
                'value' => 2.9000,
                'description' => 'Payment processor percentage fee per transaction (e.g. 2.9 = 2.9%).',
                'is_active' => true,
            ],
            [
                'key' => 'payment_processing_fee_flat',
                'value' => 0.3000,
                'description' => 'Flat payment processor fee per transaction in the campaign currency (e.g. 0.30 USD).',
                'is_active' => true,
            ],
            [
                'key' => 'withdrawal_fee_pct',
                'value' => 1.0000,
                'description' => 'Fee percentage deducted from each withdrawal request (e.g. 1 = 1%).',
                'is_active' => true,
            ],
            [
                'key' => 'minimum_donation_amount',
                'value' => 1.0000,
                'description' => 'Minimum allowed donation amount in the campaign currency.',
                'is_active' => true,
            ],
            [
                'key' => 'minimum_withdrawal_amount',
                'value' => 10.0000,
                'description' => 'Minimum amount an organizer can request in a single withdrawal.',
                'is_active' => true,
            ],
        ];

        foreach ($fees as $fee) {
            FeeConfiguration::updateOrCreate(
                ['key' => $fee['key']],
                $fee
            );
        }
    }
}
