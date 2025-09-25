<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EventSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'date' => 'datetime',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Get the event that owns this schedule.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get formatted date and time string.
     */
    public function getFormattedScheduleAttribute(): string
    {
        $date = $this->date->format('l M d Y');
        $startTime = Carbon::parse($this->start_time)->format('h:i A');
        $endTime = Carbon::parse($this->end_time)->format('h:i A');
        
        return "{$date} from {$startTime} to {$endTime}";
    }

    /**
     * Check if this schedule is in the past.
     */
    public function getIsPastAttribute(): bool
    {
        $scheduleDateTime = Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->end_time);
        return $scheduleDateTime->isPast();
    }
}
