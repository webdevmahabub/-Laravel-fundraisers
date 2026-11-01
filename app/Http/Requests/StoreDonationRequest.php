<?php

namespace App\Http\Requests;

use App\Models\Campaign;
use App\Models\FeeConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDonationRequest extends FormRequest
{
    /**
     * Payment methods that charge through a gateway integration (Stripe,
     * PayPal, bKash Merchant). No gateway is wired up in this codebase yet,
     * so every method the form can actually offer is a manual transfer and
     * the extra verification fields below are required. When a gateway is
     * integrated later, this list decides which methods can skip the manual
     * fields — and webhook verification must be added at the same time.
     */
    private const GATEWAY_SLUGS = ['stripe', 'paypal', 'bkash-merchant'];

    public function authorize(): bool
    {
        // Donations are open to everyone (including guests)
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $minimum = FeeConfiguration::getValue('minimum_donation_amount', 1.0);

        // Currency bucket matches the public payment-methods API:
        // BDT campaigns get the BDT methods, everything else the USD set.
        $campaign = Campaign::where('slug', (string) $this->route('slug'))->first();
        $bucket = $campaign && strtoupper((string) $campaign->currency) === 'BDT' ? 'BDT' : 'USD';

        return [
            'gross_amount' => ['required', 'numeric', 'min:'.$minimum, 'max:99999'],
            'donor_name' => ['required_if:is_anonymous,false', 'nullable', 'string', 'max:255'],
            'donor_email' => ['required_if:is_anonymous,false', 'nullable', 'email', 'max:255'],
            'is_anonymous' => ['boolean'],
            'message' => ['nullable', 'string', 'max:500'],
            // Must be a real, currently active payment method for this currency.
            // Unknown or disabled methods are rejected — never trusted blindly.
            'payment_method' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payment_methods', 'slug')
                    ->where(fn ($query) => $query
                        ->where('is_active', true)
                        ->where('currency_type', $bucket)
                        ->whereNotIn('slug', self::GATEWAY_SLUGS)
                    ),
            ],
            // Manual transfers: the donor must tell us who sent the money and
            // the provider's transaction reference so admins can verify it.
            'sender_mobile' => [
                Rule::requiredIf(fn () => ! in_array($this->input('payment_method'), self::GATEWAY_SLUGS, true)),
                'nullable',
                'string',
                'max:30',
            ],
            'transaction_id' => [
                Rule::requiredIf(fn () => ! in_array($this->input('payment_method'), self::GATEWAY_SLUGS, true)),
                'nullable',
                'string',
                'max:100',
                // A TrxID identifies one real transfer. Rejecting duplicates here
                // is the idempotency guard for the manual flow: the same transfer
                // can never create two donation records.
                Rule::unique('donations', 'gateway_transaction_id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gross_amount.min' => 'The minimum donation amount is '.FeeConfiguration::getValue('minimum_donation_amount', 1.0),
            'payment_method.required' => 'Please choose how you want to pay.',
            'payment_method.exists' => 'That payment method is not available for this campaign right now.',
            'sender_mobile.required' => 'Please enter the mobile number you sent the money from.',
            'transaction_id.required' => 'Please enter the transaction ID (TrxID) from your payment confirmation.',
            'transaction_id.unique' => 'That transaction ID has already been used for a donation on this platform.',
        ];
    }
}
