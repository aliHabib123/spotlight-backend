<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopCategory extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'is_active',
        'display_order',
    ];
    
    /**
     * Get the parent category of this category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'parent_id');
    }
    
    /**
     * Get the child categories of this category.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ShopCategory::class, 'parent_id');
    }
    
    /**
     * Get the shops that belong to this category.
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_shop_category');
    }
}
