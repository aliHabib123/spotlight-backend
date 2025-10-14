<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Tour extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'slug',
        'description',
        'price',
        'kids_price',
        'infant_price',
        'capacity',
        'tour_location_id',
        'display_order',
        'active',
        'is_featured',
        'user_id',
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'kids_price' => 'decimal:2',
        'infant_price' => 'decimal:2',
        'active' => 'boolean',
        'is_featured' => 'boolean',
        'capacity' => 'integer',
        'display_order' => 'integer',
    ];
    
    /**
     * Get the location that this tour belongs to
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(TourLocation::class, 'tour_location_id');
    }
    
    /**
     * Get the user (tour admin) who created this tour
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the images for this tour
     */
    public function images(): HasMany
    {
        return $this->hasMany(TourImage::class)->orderBy('display_order');
    }
    
    /**
     * Get the day availabilities for this tour
     */
    public function dayAvailabilities(): HasMany
    {
        return $this->hasMany(TourDayAvailability::class);
    }
    
    /**
     * Get the date ranges when this tour is available
     */
    public function dateRanges(): HasMany
    {
        return $this->hasMany(TourDateRange::class)->orderBy('start_date');
    }
    
    /**
     * Get the ratings for this tour
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(TourRating::class);
    }
    
    /**
     * Scope a query to only include active tours
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
    
    /**
     * Get the average rating for this tour
     */
    public function getAverageRatingAttribute(): ?float
    {
        return $this->ratings()->avg('rating');
    }
    
    /**
     * Get the review count for this tour
     */
    public function getReviewCountAttribute(): int
    {
        return $this->ratings()->count();
    }
}
