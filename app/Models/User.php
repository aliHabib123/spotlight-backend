<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\VerifyEmail;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;
    
    /**
     * Check if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at);
    }
    
    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }
    
    /**
     * Get the email address that should be used for verification.
     *
     * @return string
     */
    public function getEmailForVerification()
    {
        return $this->email;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'mobile',
        'mobile_country_code',
        'address',
        'provider_name',
        'provider_id',
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
        ];
    }
    
    /**
     * Get the spotlights that this user has created.
     */
    public function spotlights(): HasMany
    {
        return $this->hasMany(Spotlight::class);
    }
    
    /**
     * Get the spotlights that this user has saved.
     */
    public function savedSpotlights(): HasMany
    {
        return $this->hasMany(SavedSpotlight::class);
    }
    
    /**
     * Get the saved spotlight entities directly.
     */
    public function savedSpotlightEntities(): HasManyThrough
    {
        return $this->hasManyThrough(Spotlight::class, SavedSpotlight::class, 'user_id', 'id', 'id', 'spotlight_id');
    }
    
    /**
     * Get the news that this user has saved.
     */
    public function savedNews(): HasMany
    {
        return $this->hasMany(SavedNews::class);
    }
    
    /**
     * Get the saved news entities directly.
     */
    public function savedNewsEntities(): HasManyThrough
    {
        return $this->hasManyThrough(News::class, SavedNews::class, 'user_id', 'id', 'id', 'news_id');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'email_verified' => !is_null($this->email_verified_at)
        ];
    }
    
    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmail);
    }
    
    /**
     * Get the spotlight ratings that this user has created.
     */
    public function spotlightRatings(): HasMany
    {
        return $this->hasMany(SpotlightRating::class);
    }
    
    /**
     * Get the tours that this user has created.
     */
    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }
    
    /**
     * Get the tour ratings that this user has created.
     */
    public function tourRatings(): HasMany
    {
        return $this->hasMany(TourRating::class);
    }
}
