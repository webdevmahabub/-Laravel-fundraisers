<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadVerificationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = auth()->user()?->organizerProfile;

        return $profile !== null
            && in_array($profile->status, ['pending', 'rejected'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'max:10240', // 10 MB in kilobytes
                'mimes:pdf,jpg,jpeg,png,webp',
            ],
            'type' => [
                'required',
                'string',
                'in:national_id,passport,drivers_license,business_registration,utility_bill,other',
            ],
        ];
    }
}
