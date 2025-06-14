<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'type',
        'provider',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'path',
        'extension',
        'width',
        'height',
        'size',
        'provider_id',
        'provider_metadata',
        'is_featured',
        'display_order',
        'alt_text',
        'description',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'provider_metadata' => 'array',
        'is_featured' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'size' => 'integer',
    ];
    
    /**
     * Get the parent mediable model.
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
    
    /**
     * Scope a query to only include featured media.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
    
    /**
     * Scope a query to order by display order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
    
    /**
     * Scope a query to filter by media type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
    
    /**
     * Determine if this is an image.
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->type === 'image';
    }
    
    /**
     * Determine if this is a video.
     *
     * @return bool
     */
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }
    
    /**
     * Get the URL for the media.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        // Handle external providers like Vimeo
        if ($this->provider === 'vimeo' && $this->provider_id) {
            return "https://vimeo.com/{$this->provider_id}";
        }
        
        // Handle local storage
        if ($this->disk && $this->path) {
            return asset("storage/{$this->path}");
        }
        
        return '';
    }
    
    /**
     * Get the aspect ratio for images and videos.
     *
     * @return float|null
     */
    public function getAspectRatioAttribute(): ?float
    {
        if ($this->width && $this->height && $this->height > 0) {
            return $this->width / $this->height;
        }
        
        return null;
    }
}
