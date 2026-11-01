<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VerificationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_profile_id',
        'type',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size',
        'status',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function organizerProfile(): BelongsTo
    {
        return $this->belongsTo(OrganizerProfile::class);
    }

    /**
     * Generate a temporary signed URL for secure, time-limited access.
     * Documents are stored on the private 'local' disk and must never
     * be served via a public URL.
     */
    public function temporaryUrl(int $expirationMinutes = 15): string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $this->path,
            now()->addMinutes($expirationMinutes)
        );
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
