<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotlightCategoryAttribute extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'attribute_definition_id',
        'is_required',
        'is_featured',
        'display_order',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_required' => 'boolean',
        'is_featured' => 'boolean',
    ];
    
    /**
     * Get the category associated with this pivot.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SpotlightCategory::class);
    }
    
    /**
     * Get the attribute definition associated with this pivot.
     */
    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(SpotlightAttributeDefinition::class);
    }
    
    /**
     * Determine if the attribute is required for this category.
     * Falls back to the attribute definition's is_required value if not set in pivot.
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        // If is_required is explicitly set in the pivot, use that value
        if ($this->is_required !== null) {
            return $this->is_required;
        }
        
        // Otherwise, fall back to the attribute definition's is_required value
        return $this->attributeDefinition->is_required;
    }
}
