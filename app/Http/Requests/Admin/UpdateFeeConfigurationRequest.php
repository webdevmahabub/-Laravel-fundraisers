<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFeeConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'fees' => ['required', 'array'],
            'fees.platform_fee_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'fees.payment_processing_fee_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'fees.payment_processing_fee_flat' => ['required', 'numeric', 'min:0'],
            'fees.withdrawal_fee_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'fees.minimum_donation_amount' => ['required', 'numeric', 'min:0'],
            'fees.minimum_withdrawal_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
