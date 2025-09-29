<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourLocation extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
    ];
    
    /**
     * Get the tours for this location
     */
    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }
}
