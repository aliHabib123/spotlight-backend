<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_number',
        'user_id',
        'shipping_address_id',
        'subtotal',
        'tax',
        'shipping_cost',
        'total',
        'payment_method',
        'payment_status',
        'order_status',
        'notes',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];
    
    protected $casts = [
        'subtotal' => 'float',
        'tax' => 'float',
        'shipping_cost' => 'float',
        'total' => 'float',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($order) {
            // Generate a unique order number if not provided
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }
    
    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'ORD-';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(Str::random(4));
        
        $orderNumber = $prefix . $timestamp . '-' . $random;
        
        // Ensure uniqueness
        while (static::where('order_number', $orderNumber)->exists()) {
            $random = strtoupper(Str::random(4));
            $orderNumber = $prefix . $timestamp . '-' . $random;
        }
        
        return $orderNumber;
    }
    
    /**
     * Get the user that placed the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the shipping address for the order.
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(ShippingAddress::class);
    }
    
    /**
     * Get the items in the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    
    /**
     * Check if the order is paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' && $this->paid_at !== null;
    }
    
    /**
     * Check if the order is shipped.
     */
    public function isShipped(): bool
    {
        return $this->order_status === 'shipped' && $this->shipped_at !== null;
    }
    
    /**
     * Check if the order is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->order_status === 'delivered' && $this->delivered_at !== null;
    }
    
    /**
     * Check if the order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->order_status === 'cancelled' && $this->cancelled_at !== null;
    }
    
    /**
     * Mark the order as paid.
     */
    public function markAsPaid(string $paymentMethod = null)
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $paymentMethod ?? $this->payment_method,
        ]);
        
        return $this;
    }
    
    /**
     * Mark the order as shipped.
     */
    public function markAsShipped()
    {
        $this->update([
            'order_status' => 'shipped',
            'shipped_at' => now(),
        ]);
        
        return $this;
    }
    
    /**
     * Mark the order as delivered.
     */
    public function markAsDelivered()
    {
        $this->update([
            'order_status' => 'delivered',
            'delivered_at' => now(),
        ]);
        
        return $this;
    }
    
    /**
     * Mark the order as cancelled.
     */
    public function markAsCancelled()
    {
        $this->update([
            'order_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
        
        return $this;
    }
    
    /**
     * Create an order from a cart.
     */
    public static function createFromCart(Cart $cart, ShippingAddress $shippingAddress, float $shippingCost = 0, float $tax = 0, array $additionalData = [])
    {
        // Calculate subtotal from cart items
        $subtotal = $cart->items->reduce(function ($carry, $item) {
            return $carry + ($item->price * $item->quantity);
        }, 0);
        
        // Calculate total
        $total = $subtotal + $shippingCost + $tax;
        
        // Create the order
        $order = static::create([
            'user_id' => $cart->user_id,
            'shipping_address_id' => $shippingAddress->id,
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'tax' => $tax,
            'total' => $total,
            'payment_method' => $additionalData['payment_method'] ?? null,
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'notes' => $additionalData['notes'] ?? null,
        ]);
        
        // Create order items from cart items
        foreach ($cart->items as $cartItem) {
            $product = $cartItem->product;
            $variation = $cartItem->variation;
            
            $orderItem = new OrderItem([
                'product_id' => $cartItem->product_id,
                'product_variation_id' => $cartItem->product_variation_id,
                'product_name' => $product->name,
                'variation_name' => $variation ? $variation->name : null,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->price,
                'subtotal' => $cartItem->price * $cartItem->quantity,
                'product_data' => $product->toArray(),
                'variation_data' => $variation ? $variation->toArray() : null,
            ]);
            
            $order->items()->save($orderItem);
        }
        
        // Clear the cart after order is created
        $cart->items()->delete();
        $cart->delete();
        
        return $order;
    }
}
