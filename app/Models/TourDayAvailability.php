<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourDayAvailability extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'tour_id',
        'day',
    ];
    
    /**
     * Get the tour that owns this availability
     */
    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
