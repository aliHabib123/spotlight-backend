<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpotlightAttributeDefinition extends Model
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
        'description',
        'validation_rules',
        'is_required',
        'is_filterable',
        'allows_multiple',
        'display_type',
        'display_order',
        'parent_id',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'is_filterable' => 'boolean',
        'allows_multiple' => 'boolean',
    ];
    
    /**
     * Get the categories that use this attribute definition.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(SpotlightCategory::class, 'spotlight_category_attributes', 'attribute_definition_id', 'category_id')
            ->withPivot(['is_required', 'is_featured', 'display_order'])
            ->withTimestamps();
    }
    
    /**
     * Alias for categories() - needed for Filament's attach action
     */
    public function spotlightCategories(): BelongsToMany
    {
        return $this->categories();
    }
    
    /**
     * Get all options for this attribute definition (for enum type).
     */
    public function options(): HasMany
    {
        return $this->hasMany(SpotlightAttributeOption::class, 'attribute_definition_id')
            ->orderBy('display_order');
    }
    
    /**
     * Get all values assigned for this attribute definition.
     */
    public function values(): HasMany
    {
        return $this->hasMany(SpotlightAttributeValue::class, 'attribute_definition_id');
    }
    
    /**
     * Get the parent attribute definition.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(SpotlightAttributeDefinition::class, 'parent_id');
    }
    
    /**
     * Get the child attribute definitions.
     */
    public function children(): HasMany
    {
        return $this->hasMany(SpotlightAttributeDefinition::class, 'parent_id');
    }
    
    /**
     * Check if this attribute has a parent.
     *
     * @return bool
     */
    public function hasParent(): bool
    {
        return $this->parent_id !== null;
    }
    
    /**
     * Check if this attribute has children.
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }
    
    /**
     * Check if this attribute is an enum type.
     *
     * @return bool
     */
    public function isEnum(): bool
    {
        return $this->type === 'enum';
    }
    
    /**
     * Get the appropriate form field type based on the attribute type.
     *
     * @return string
     */
    public function getFormFieldType(): string
    {
        if ($this->display_type) {
            return $this->display_type;
        }
        
        // Default field types based on attribute type
        return match($this->type) {
            'string' => 'text',
            'number' => 'number',
            'boolean' => 'toggle',
            'enum' => $this->allows_multiple ? 'multiselect' : 'select',
            default => 'text',
        };
    }
}
