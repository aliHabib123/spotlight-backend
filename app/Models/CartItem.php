<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variation_id',
        'quantity',
        'price',
        'attributes',
    ];
    
    protected $casts = [
        'attributes' => 'array',
    ];
    
    /**
     * Get the cart that owns this item.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
    
    /**
     * Get the product for this cart item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * Get the product variation for this cart item (if applicable).
     */
    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }
    
    /**
     * Calculate the subtotal for this item.
     */
    public function getSubtotalAttribute(): float
    {
        return $this->price * $this->quantity;
    }
    
    /**
     * Set the price based on the product or variation.
     */
    public function setPriceFromProduct()
    {
        if ($this->product_variation_id) {
            $variation = ProductVariation::find($this->product_variation_id);
            $this->price = $variation->getCurrentPrice();
        } else {
            $product = Product::find($this->product_id);
            $this->price = $product->is_on_sale && $product->sale_price ? $product->sale_price : $product->price;
        }
        
        return $this;
    }
}
