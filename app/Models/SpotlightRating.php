<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotlightRating extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'spotlight_id',
        'rating',
        'comment',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
    ];
    
    /**
     * Get the user that created this rating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the spotlight that was rated.
     */
    public function spotlight(): BelongsTo
    {
        return $this->belongsTo(Spotlight::class);
    }
}
