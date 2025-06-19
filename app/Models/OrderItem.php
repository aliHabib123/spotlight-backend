<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variation_id',
        'product_name',
        'variation_name',
        'quantity',
        'unit_price',
        'subtotal',
        'product_data',
        'variation_data',
    ];
    
    protected $casts = [
        'unit_price' => 'float',
        'subtotal' => 'float',
        'product_data' => 'array',
        'variation_data' => 'array',
    ];
    
    /**
     * Get the order that owns this item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Get the product for this order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    
    /**
     * Get the product variation for this order item (if applicable).
     */
    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }
    
    /**
     * Calculate the subtotal for this item.
     */
    public function calculateSubtotal()
    {
        $this->subtotal = $this->unit_price * $this->quantity;
        return $this;
    }
}
