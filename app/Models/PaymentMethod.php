<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'currency_type',
        'icon',
        'description',
        'payment_instructions',
        'account_holder_name',
        'account_number',
        'bank_name',
        'branch_name',
        'routing_number',
        'phone_number',
        'qr_code_image_path',
        'config',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function booted(): void
    {
        static::creating(function (PaymentMethod $method) {
            if (empty($method->slug)) {
                $method->slug = Str::slug($method->name);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForCurrency(Builder $query, string $currencyType): Builder
    {
        return $query->where('currency_type', $currencyType);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
