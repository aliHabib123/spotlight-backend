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
        'social_links' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];
    
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
