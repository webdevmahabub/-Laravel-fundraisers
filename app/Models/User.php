<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use HasinHayder\Tyro\Concerns\HasTyroRoles;
use HasinHayder\TyroLogin\Traits\HasTwoFactorAuth;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasTwoFactorAuth, HasTyroRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizerProfile(): HasOne
    {
        return $this->hasOne(OrganizerProfile::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function campaignUpdates(): HasMany
    {
        return $this->hasMany(CampaignUpdate::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_user_id');
    }

    /**
     * Whether this user has an approved organizer profile,
     * meaning they are permitted to create and manage campaigns.
     */
    public function isApprovedOrganizer(): bool
    {
        return $this->organizerProfile?->isApproved() ?? false;
    }

    /**
     * Whether this user has submitted (or is in the process of) an
     * organizer application, regardless of its current status.
     */
    public function hasOrganizerApplication(): bool
    {
        return $this->organizerProfile !== null;
    }
}
