<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingAddress extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'phone_number',
        'is_default',
        'delivery_instructions',
    ];
    
    protected $casts = [
        'is_default' => 'boolean',
    ];
    
    /**
     * Get the user that owns this shipping address.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the orders that use this shipping address.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    
    /**
     * Get the full name of the recipient.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
    /**
     * Get the formatted address.
     */
    public function getFormattedAddressAttribute(): string
    {
        $address = $this->address_line_1;
        
        if ($this->address_line_2) {
            $address .= ", {$this->address_line_2}";
        }
        
        $address .= ", {$this->city}";
        
        if ($this->state_province) {
            $address .= ", {$this->state_province}";
        }
        
        if ($this->postal_code) {
            $address .= " {$this->postal_code}";
        }
        
        $address .= ", {$this->country}";
        
        return $address;
    }
    
    /**
     * Set this address as the default and unset any other default addresses for this user.
     */
    public function setAsDefault()
    {
        if ($this->is_default) {
            return $this;
        }
        
        // Unset any other default addresses for this user
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
        
        $this->update(['is_default' => true]);
        
        return $this;
    }
}
