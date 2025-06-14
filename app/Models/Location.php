<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'additional_info',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'additional_info' => 'array',
    ];
    
    /**
     * Get the spotlights associated with this location.
     */
    public function spotlights(): HasMany
    {
        return $this->hasMany(Spotlight::class);
    }
    
    /**
     * Get the full address as a string.
     *
     * @return string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ];
        
        // Filter out empty parts
        $parts = array_filter($parts);
        
        return implode(', ', $parts);
    }
}
