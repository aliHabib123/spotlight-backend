<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class Spotlight extends Model
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
        'short_description',
        'category_id',
        'location_id',
        'user_id',
        'contact_info',
        'social_links',
        'opening_hours',
        'video_url',
        'video_provider',
        'featured_image',
        'is_featured',
        'is_trending',
        'is_published',
        'published_at',
        'average_rating',
        'review_count',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'contact_info' => 'array',
        'social_links' => 'array',
        'opening_hours' => 'array',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'average_rating' => 'integer',
        'review_count' => 'integer',
    ];
    
    /**
     * Get the category that owns the spotlight.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SpotlightCategory::class, 'category_id');
    }
    
    /**
     * Get the location that owns the spotlight.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
    
    /**
     * Get the user that created the spotlight.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get all tags for this spotlight.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
    
    /**
     * Get all attribute values for this spotlight.
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(SpotlightAttributeValue::class);
    }
    
    /**
     * Get all media for this spotlight.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }
    
    /**
     * Get attribute values organized by attribute definition.
     * 
     * @return array
     */
    public function getAttributesByDefinition(): array
    {
        $attributes = [];
        
        foreach ($this->attributeValues as $value) {
            $definition = $value->attributeDefinition;
            if (!isset($attributes[$definition->id])) {
                $attributes[$definition->id] = [
                    'definition' => $definition,
                    'values' => [],
                ];
            }
            
            if ($value->attribute_option_id) {
                $attributes[$definition->id]['values'][] = $value->attributeOption;
            } else {
                $attributes[$definition->id]['values'][] = $value->value;
            }
        }
        
        return $attributes;
    }
    
    /**
     * Scope a query to only include published spotlights.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
    
    /**
     * Scope a query to only include featured spotlights.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
    
    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }
    
    /**
     * Scope a query to filter by tag.
     */
    public function scopeByTag(Builder $query, int $tagId): Builder
    {
        return $query->whereHas('tags', function($q) use ($tagId) {
            $q->where('tags.id', $tagId);
        });
    }
    
    /**
     * Scope a query to filter by attribute value.
     */
    public function scopeByAttribute(Builder $query, int $attributeId, $value): Builder
    {
        return $query->whereHas('attributeValues', function($q) use ($attributeId, $value) {
            $q->where('attribute_definition_id', $attributeId)
              ->where(function($q) use ($value) {
                  $q->where('value', $value)
                    ->orWhereHas('attributeOption', function($q) use ($value) {
                        $q->where('id', $value);
                    });
              });
        });
    }
    
    /**
     * Get the users who have saved this spotlight.
     */
    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_spotlights', 'spotlight_id', 'user_id')
                    ->withTimestamps();
    }
    
    /**
     * Get the saved spotlight records.
     */
    public function savedRecords(): HasMany
    {
        return $this->hasMany(SavedSpotlight::class);
    }
}
