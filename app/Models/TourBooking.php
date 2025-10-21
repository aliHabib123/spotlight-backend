<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'tour_id',
        'user_id',
        'selected_date',
        'adults',
        'kids',
        'infants',
        'total_price',
        'status',
        'full_name',
        'email',
        'phone',
        'special_requests',
    ];

    protected $casts = [
        'selected_date' => 'date',
        'adults' => 'integer',
        'kids' => 'integer',
        'infants' => 'integer',
        'total_price' => 'decimal:2',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
