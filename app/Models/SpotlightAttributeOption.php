<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpotlightAttributeOption extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'attribute_definition_id',
        'value',
        'label',
        'color',
        'display_order',
        'parent_option_id',
    ];
    
    /**
     * Get the attribute definition that this option belongs to.
     */
    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(SpotlightAttributeDefinition::class);
    }
    
    /**
     * Get all attribute values that reference this option.
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(SpotlightAttributeValue::class, 'attribute_option_id');
    }
    
    /**
     * Get the display label (falls back to value if label is not set).
     *
     * @return string
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?? $this->value;
    }
    
    /**
     * Get the parent option that this option belongs to.
     */
    public function parentOption(): BelongsTo
    {
        return $this->belongsTo(SpotlightAttributeOption::class, 'parent_option_id');
    }
    
    /**
     * Get the child options that belong to this option.
     */
    public function childOptions(): HasMany
    {
        return $this->hasMany(SpotlightAttributeOption::class, 'parent_option_id');
    }
    
    /**
     * Check if this option has a parent option.
     *
     * @return bool
     */
    public function hasParent(): bool
    {
        return $this->parent_option_id !== null;
    }
    
    /**
     * Check if this option has child options.
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->childOptions()->exists();
    }
    
    /**
     * Get all descendant option IDs recursively to prevent circular references.
     *
     * @return array
     */
    public function getAllChildrenIds(): array
    {
        $childrenIds = [];
        
        // Get immediate children
        $children = $this->childOptions()->get();
        
        foreach ($children as $child) {
            $childrenIds[] = $child->id;
            
            // Recursively get grandchildren
            $grandchildrenIds = $child->getAllChildrenIds();
            if (!empty($grandchildrenIds)) {
                $childrenIds = array_merge($childrenIds, $grandchildrenIds);
            }
        }
        
        return $childrenIds;
    }
}
