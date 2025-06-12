<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'shop_id',
        'name',
        'slug',
        'description',
        'type',
        'price',
        'sale_price',
        'is_on_sale',
        'stock',
        'is_active',
        'is_featured',
        'images',
        'attributes',
    ];
    
    protected $casts = [
        'images' => 'array',
        'attributes' => 'array',
        'is_on_sale' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];
    
    /**
     * Get the shop that this product belongs to.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
    
    /**
     * Get the variations for this product.
     */
    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }
    
    /**
     * Determine if the product is a variable product.
     */
    public function isVariable(): bool
    {
        return $this->type === 'variable';
    }
    
    /**
     * Determine if the product is a single product.
     */
    public function isSingle(): bool
    {
        return $this->type === 'single';
    }
}
