<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ad extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'image',
        'link_url',
        'media_type',
        'video_url',
        'ad_location_id',
        'user_id',
        'is_active',
        'display_order',
        'start_date',
        'end_date',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'media_type' => 'string',
    ];
    
    /**
     * Get the YouTube video ID from the video URL.
     *
     * @return string|null
     */
    public function getYoutubeEmbedAttribute(): ?string
    {
        if ($this->media_type !== 'youtube' || empty($this->video_url)) {
            return null;
        }
        
        $videoId = null;
        
        // Parse YouTube URL to extract video ID
        if (preg_match('/youtube\.com\/watch\?v=([\w-]+)/', $this->video_url, $matches)) {
            $videoId = $matches[1];
        } elseif (preg_match('/youtu\.be\/([\w-]+)/', $this->video_url, $matches)) {
            $videoId = $matches[1];
        } elseif (preg_match('/youtube\.com\/embed\/([\w-]+)/', $this->video_url, $matches)) {
            $videoId = $matches[1];
        }
        
        return $videoId;
    }
    
    /**
     * Get the location that owns the ad.
     */
    public function adLocation(): BelongsTo
    {
        return $this->belongsTo(AdLocation::class);
    }
    
    /**
     * Get the user that owns the ad.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Scope a query to only include active ads.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }
}
