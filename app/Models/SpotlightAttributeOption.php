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
}
