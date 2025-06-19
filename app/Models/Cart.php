<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'session_id',
    ];
    
    /**
     * Get the user that owns the cart.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the items in the cart.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
    
    /**
     * Calculate the total price of all items in the cart.
     */
    public function getTotalAttribute(): float
    {
        return $this->items->reduce(function ($carry, $item) {
            return $carry + ($item->price * $item->quantity);
        }, 0);
    }
    
    /**
     * Calculate the total number of items in the cart.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }
    
    /**
     * Find a cart by user ID or session ID.
     */
    public static function findByUserOrSession($userId = null, $sessionId = null)
    {
        if ($userId) {
            return self::where('user_id', $userId)->first();
        }
        
        if ($sessionId) {
            return self::where('session_id', $sessionId)->first();
        }
        
        return null;
    }
    
    /**
     * Transfer items from a session cart to a user cart.
     */
    public static function mergeSessionCartWithUserCart($sessionId, $userId)
    {
        $sessionCart = self::where('session_id', $sessionId)->first();
        $userCart = self::where('user_id', $userId)->first();
        
        if (!$sessionCart) {
            return $userCart ?? self::create(['user_id' => $userId]);
        }
        
        if (!$userCart) {
            // Just update the session cart to be owned by the user
            $sessionCart->update(['user_id' => $userId, 'session_id' => null]);
            return $sessionCart;
        }
        
        // Merge the items from session cart into user cart
        foreach ($sessionCart->items as $item) {
            $existingItem = $userCart->items()
                ->where('product_id', $item->product_id)
                ->where('product_variation_id', $item->product_variation_id)
                ->first();
            
            if ($existingItem) {
                // Update quantity if item already exists
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $item->quantity
                ]);
            } else {
                // Move item to user cart
                $item->update(['cart_id' => $userCart->id]);
            }
        }
        
        // Delete the now-empty session cart
        $sessionCart->delete();
        
        return $userCart;
    }
}
