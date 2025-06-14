<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotlightAttributeValue extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'spotlight_id',
        'attribute_definition_id',
        'attribute_option_id',
        'value',
    ];
    
    /**
     * Get the spotlight that this value belongs to.
     */
    public function spotlight(): BelongsTo
    {
        return $this->belongsTo(Spotlight::class);
    }
    
    /**
     * Get the attribute definition that this value is for.
     */
    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(SpotlightAttributeDefinition::class);
    }
    
    /**
     * Get the attribute option if this is an enum value.
     */
    public function attributeOption(): BelongsTo
    {
        return $this->belongsTo(SpotlightAttributeOption::class);
    }
    
    /**
     * Get the displayed value, which may be from the option label or the raw value.
     *
     * @return mixed
     */
    public function getDisplayValueAttribute(): mixed
    {
        if ($this->attribute_option_id) {
            return $this->attributeOption->display_label;
        }
        
        return $this->value;
    }
    
    /**
     * Cast the value to the appropriate type based on the attribute definition.
     *
     * @return mixed
     */
    public function getTypedValueAttribute(): mixed
    {
        if ($this->attribute_option_id) {
            return $this->attributeOption;
        }
        
        $type = $this->attributeDefinition->type;
        
        return match($type) {
            'boolean' => (bool) $this->value,
            'number' => is_numeric($this->value) ? (float) $this->value : $this->value,
            default => $this->value,
        };
    }
}
