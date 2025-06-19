<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeScreenLocation extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'display_order'
    ];

    /**
     * Get the spotlight categories for this home screen location
     */
    public function spotlightCategories()
    {
        return $this->hasMany(SpotlightCategory::class);
    }
}
