<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FeeConfiguration extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Retrieve the value of a fee configuration key.
     * Returns the provided default if the key is not found or inactive.
     */
    public static function getValue(string $key, float $default = 0.0): float
    {
        $record = static::where('key', $key)
            ->where('is_active', true)
            ->first();

        return $record ? (float) $record->value : $default;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
