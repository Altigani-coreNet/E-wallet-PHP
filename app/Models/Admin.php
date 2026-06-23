<?php

namespace App\Models;

use App\Traits\AppliesCountryScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\HasStatus;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Passport\HasApiTokens;
use App\Scopes\CountryScope;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, HasStatus, HasRoles, HasApiTokens, AppliesCountryScope, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'profile_image',
        'status',
        'custom_region',
        'country_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'custom_region' => 'boolean',
        ];
    }

    /**
     * Regions assigned to this admin (mapped to countries table via pivot admin_countries)
     */
    public function countries()
    {
        return $this->belongsToMany(Country::class, 'admin_countries')->withTimestamps();
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the admin's profile image for table display
     */
    public function getTableImage(): string
    {
        if ($this->profile_image) {
            return "<div class='cursor-pointer symbol symbol-35px symbol-md-40px'>" .
                "<img src='" . asset($this->profile_image) . "' alt='Admin Image' width='60' height='60'/>"
                . "</div>";
        }
        return "<img src='" . asset("assets/media/avatars/300-1.jpg") . "' alt='Admin Image' width='60' height='60' />";
    }

    /**
     * Get the admin's profile image for API responses
     */
    public function getProfileImageApi(): string
    {
        if ($this->profile_image) {
            return asset($this->profile_image);
        }
        return asset('assets/media/avatars/300-1.jpg');
    }

    /**
     * Get the OAuth provider name for this model.
     * This tells Passport to use the 'admins' provider when creating tokens.
     */
    public function getOAuthProviderName(): string
    {
        return 'admins';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Add global scope to automatically filter by regions from X-Regions header
        static::addGlobalScope(new CountryScope());
    }
} 