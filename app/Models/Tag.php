<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
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
        'type',
        'color',
        'display_order',
        'category_id',
    ];
    
    /**
     * Get all spotlights that have this tag.
     */
    public function spotlights(): BelongsToMany
    {
        return $this->belongsToMany(Spotlight::class);
    }
    
    /**
     * Get the category that this tag belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SpotlightCategory::class, 'category_id');
    }
    
    /**
     * Scope a query to only include tags of a specified type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
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
}
