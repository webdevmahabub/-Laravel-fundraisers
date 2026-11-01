<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            // BDT Methods
            [
                'name' => 'bKash',
                'currency_type' => 'BDT',
                'icon' => '📱',
                'description' => 'Send money via bKash mobile wallet',
                'payment_instructions' => "1. Open your bKash app\n2. Tap 'Send Money'\n3. Enter the account number below\n4. Enter the exact donation amount\n5. Enter the reference: Fundraiser Donation\n6. Confirm with your PIN\n7. Copy the TrxID and paste it in the note field below",
                'account_holder_name' => 'Fundraiser BD',
                'account_number' => '01712345678',
                'phone_number' => '+880 1712 345678',
                'sort_order' => 1,
            ],
            [
                'name' => 'Nagad',
                'currency_type' => 'BDT',
                'icon' => '💰',
                'description' => 'Send money via Nagad digital wallet',
                'payment_instructions' => "1. Open your Nagad app\n2. Tap 'Send Money'\n3. Enter the account number below\n4. Enter the exact donation amount\n5. Add reference: Fundraiser Donation\n6. Confirm with your PIN\n7. Save the Transaction ID",
                'account_holder_name' => 'Fundraiser BD',
                'account_number' => '01812345678',
                'phone_number' => '+880 1812 345678',
                'sort_order' => 2,
            ],
            [
                'name' => 'Bank Transfer',
                'currency_type' => 'BDT',
                'icon' => '🏦',
                'description' => 'Direct bank transfer (DBBL, Brac, City, etc.)',
                'payment_instructions' => "1. Log in to your bank's mobile/online banking\n2. Select 'Send Money' or 'Transfer'\n3. Enter the account details below\n4. Enter the exact donation amount\n5. Use reference: Fundraiser Donation\n6. Confirm the transfer\n7. Take a screenshot of the confirmation",
                'account_holder_name' => 'Fundraiser Foundation BD',
                'account_number' => '1234567890123',
                'bank_name' => 'Dutch-Bangla Bank Limited',
                'branch_name' => 'Dhanmondi Branch',
                'routing_number' => '090201203',
                'sort_order' => 3,
            ],

            // USD Methods
            [
                'name' => 'Stripe',
                'currency_type' => 'USD',
                'icon' => '💳',
                'description' => 'Pay securely with credit/debit card via Stripe',
                'payment_instructions' => 'Click the "Donate Now" button below. You will be redirected to our secure Stripe checkout page where you can enter your card details. All transactions are encrypted and secure.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Wise',
                'currency_type' => 'USD',
                'icon' => '🌍',
                'description' => 'International transfer via Wise (formerly TransferWise)',
                'payment_instructions' => "1. Open your Wise account\n2. Click 'Send Money'\n3. Enter the details below\n4. Enter the donation amount in USD\n5. Add reference: Fundraiser Donation\n6. Confirm the transfer\n7. Share the confirmation screenshot",
                'account_holder_name' => 'Fundraiser Foundation',
                'account_number' => 'wise@fundraiser.org',
                'sort_order' => 2,
            ],
            [
                'name' => 'PayPal',
                'currency_type' => 'USD',
                'icon' => '🅿️',
                'description' => 'Pay with PayPal balance or linked account',
                'payment_instructions' => 'Click the "Donate Now" button below. You will be redirected to PayPal where you can pay using your PayPal balance, bank account, or card linked to PayPal.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Bank Transfer',
                'currency_type' => 'USD',
                'icon' => '🏦',
                'description' => 'International wire transfer (SWIFT/IBAN)',
                'payment_instructions' => "1. Log in to your bank's international wire transfer portal\n2. Enter the SWIFT/IBAN details below\n3. Enter the donation amount in USD\n4. Use reference: Fundraiser Donation + Your Name\n5. Confirm the wire transfer\n6. Email the transfer receipt to donations@fundraiser.org",
                'account_holder_name' => 'Fundraiser Foundation Inc.',
                'account_number' => 'US12345678901234567890',
                'bank_name' => 'Chase Bank',
                'branch_name' => 'New York Branch',
                'routing_number' => 'CHASUS33',
                'sort_order' => 4,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['slug' => Str::slug($method['name']).'-'.strtolower($method['currency_type'])],
                array_merge($method, ['is_active' => true])
            );
        }
    }
}
