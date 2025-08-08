<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'news';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'featured_image',
        'news_category_id',
        'user_id',
        'is_published',
        'is_featured',
        'published_at',
        'show_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'show_date' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($news) {
            if (empty($news->slug)) {
                $news->slug = Str::slug($news->title);
            }

            if ($news->is_published && empty($news->published_at)) {
                $news->published_at = now();
            }
        });

        static::updating(function ($news) {
            if ($news->isDirty('title') && empty($news->slug)) {
                $news->slug = Str::slug($news->title);
            }

            if ($news->isDirty('is_published') && $news->is_published && empty($news->published_at)) {
                $news->published_at = now();
            }
        });
    }

    /**
     * Get the category that owns the news.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    /**
     * Get the user that owns the news.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope a query to only include published news.
     */
    /**
     * Get the users who saved this news.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function savedBy()
    {
        return $this->hasManyThrough(User::class, SavedNews::class, 'news_id', 'id', 'id', 'user_id');
    }
    
    /**
     * Get saved news records for this news.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function savedRecords()
    {
        return $this->hasMany(SavedNews::class);
    }
    
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }
}
