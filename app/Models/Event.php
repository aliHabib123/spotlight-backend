<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'image',
        'event_location_id',
        'map_url',
        'phone_number',
        'event_category_id',
        'is_featured',
        'is_published',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    /**
     * Get the category that owns the event.
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class);
    }

    /**
     * Get the location that owns the event.
     */
    public function eventLocation(): BelongsTo
    {
        return $this->belongsTo(EventLocation::class);
    }

    /**
     * Get all schedules for this event.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(EventSchedule::class)->orderBy('date')->orderBy('start_time');
    }

    /**
     * Scope a query to only include published events.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to only include featured events.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get the next upcoming schedule for this event.
     */
    public function getNextScheduleAttribute()
    {
        return $this->schedules()
            ->where('date', '>=', now()->toDateString())
            ->first();
    }

    /**
     * Get phone number, return empty string if null.
     */
    public function getPhoneNumberAttribute($value): string
    {
        return $value ?? '';
    }
}
