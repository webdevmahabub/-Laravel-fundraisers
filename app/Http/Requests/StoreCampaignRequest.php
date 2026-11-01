<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:campaign_categories,id'],
            'short_description' => ['required', 'string', 'max:500'],
            'story' => ['required', 'string', 'min:100'],
            'goal_amount' => ['required', 'numeric', 'min:1', 'max:9999999'],
            'currency' => ['nullable', 'string', 'size:3'],
            'start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
