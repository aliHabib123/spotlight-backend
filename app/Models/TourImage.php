<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourImage extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'tour_id',
        'image_path',
        'display_order',
    ];
    
    /**
     * Get the tour that owns this image
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
