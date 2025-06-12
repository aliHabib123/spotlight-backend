<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariation extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'product_id',
        'name',
        'attributes',
        'price',
        'sale_price',
        'is_on_sale',
        'stock',
        'sku',
        'is_active',
        'image',
    ];
    
    protected $casts = [
        'attributes' => 'array',
        'is_on_sale' => 'boolean',
        'is_active' => 'boolean',
    ];
    
    /**
     * Get the product that this variation belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * Get the current price of the variation (either sale price or regular price).
     */
    public function getCurrentPrice(): float
    {
        if ($this->is_on_sale && $this->sale_price !== null) {
            return (float) $this->sale_price;
        }
        
        return (float) $this->price;
    }
}
