<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'phone',
        'email',
        'website',
        'logo',
        'cover_image',
        'social_links',
        'is_active',
        'is_featured',
        'display_order',
    ];
    
    protected $casts = [
        'social_links' => 'json',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];
    
    /**
     * Get the social links attribute.
     *
     * @param  mixed  $value
     * @return array
     */
    public function getSocialLinksAttribute($value)
    {
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return is_array($value) ? $value : [];    
    }
    
    /**
     * Set the social links attribute.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setSocialLinksAttribute($value)
    {
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->attributes['social_links'] = json_encode($decoded);
                return;
            }
        }
        
        $this->attributes['social_links'] = is_array($value) ? json_encode($value) : json_encode([]);
    }
    
    /**
     * Get the categories that this shop belongs to.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ShopCategory::class, 'shop_shop_category');
    }
    
    /**
     * Get the products that belong to this shop.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
