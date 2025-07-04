<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpotlightCategory extends Model
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
        'icon',
        'description',
        'parent_id',
        'is_active',
        'display_order',
        'home_screen_location_id',
        'show_location_filter',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'show_location_filter' => 'boolean',
    ];
    
    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(SpotlightCategory::class, 'parent_id');
    }
    
    /**
     * Get the subcategories for this category.
     */
    public function children(): HasMany
    {
        return $this->hasMany(SpotlightCategory::class, 'parent_id');
    }
    
    /**
     * Get all spotlights belonging to this category.
     */
    public function spotlights(): HasMany
    {
        return $this->hasMany(Spotlight::class, 'category_id');
    }
    
    /**
     * Get all attribute definitions associated with this category.
     */
    public function attributeDefinitions(): BelongsToMany
    {
        return $this->belongsToMany(SpotlightAttributeDefinition::class, 'spotlight_category_attributes', 'category_id', 'attribute_definition_id')
            ->withPivot(['is_required', 'is_featured', 'display_order'])
            ->withTimestamps();
    }
    
    /**
     * Scope a query to only include active categories.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope a query to order by display_order.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
    
    /**
     * Get the home screen location for this category.
     */
    public function homeScreenLocation(): BelongsTo
    {
        return $this->belongsTo(HomeScreenLocation::class);
    }
}
