<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourRating extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'tour_id',
        'user_id',
        'rating',
        'comment',
    ];
    
    protected $casts = [
        'rating' => 'integer',
    ];
    
    /**
     * Get the tour that owns this rating
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
    
    /**
     * Get the user who created this rating
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
