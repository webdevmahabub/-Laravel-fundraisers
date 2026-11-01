<?php

namespace App\Http\Requests;

use App\Models\FeeConfiguration;
use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isApprovedOrganizer() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $minimum = FeeConfiguration::getValue('minimum_withdrawal_amount', 10.0);

        return [
            'amount' => ['required', 'numeric', 'min:'.$minimum, 'max:9999999'],
            'payment_method' => ['required', 'string', 'in:bank_transfer,paypal,stripe,other'],
            'payment_details' => ['nullable', 'array'],
            'account_details' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'The minimum withdrawal amount is '.FeeConfiguration::getValue('minimum_withdrawal_amount', 10.0),
        ];
    }
}
