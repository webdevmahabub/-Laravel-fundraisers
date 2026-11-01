<?php

namespace App\Models;

use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'organizer_profile_id',
        'category_id',
        'title',
        'slug',
        'short_description',
        'story',
        'goal_amount',
        'currency',
        'cover_image_path',
        'status',
        'is_verified',
        'start_date',
        'end_date',
    ];

    /**
     * Financial fields that must never be mass-assigned.
     * raised_amount is updated only through DonationService inside a DB transaction.
     */
    protected $guarded = [
        'raised_amount',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'goal_amount' => 'decimal:2',
            'raised_amount' => 'decimal:2',
            'is_verified' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function organizerProfile(): BelongsTo
    {
        return $this->belongsTo(OrganizerProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CampaignCategory::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(CampaignImage::class)->orderBy('sort_order');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(CampaignUpdate::class)->latest();
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * Returns the fundraising progress as a percentage (0–100).
     */
    public function progressPercentage(): float
    {
        if ($this->goal_amount <= 0) {
            return 0.0;
        }

        return min(100.0, round(($this->raised_amount / $this->goal_amount) * 100, 2));
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'completed', 'paused']);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
