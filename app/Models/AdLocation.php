<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AdLocation extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];
    
    /**
     * Get the ads for this location.
     */
    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
    
    /**
     * Get only active ads for this location.
     */
    public function activeAds(): HasMany
    {
        return $this->ads()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderBy('display_order');
    }
    
    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($adLocation) {
            if (empty($adLocation->slug)) {
                $adLocation->slug = Str::slug($adLocation->name);
            }
        });

        static::updating(function ($adLocation) {
            if ($adLocation->isDirty('name') && empty($adLocation->slug)) {
                $adLocation->slug = Str::slug($adLocation->name);
            }
        });
    }
}
