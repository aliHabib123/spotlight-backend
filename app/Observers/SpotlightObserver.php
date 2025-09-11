<?php

namespace App\Observers;

use App\Models\Spotlight;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;

class SpotlightObserver
{
    protected $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Handle the Spotlight "created" event.
     */
    public function created(Spotlight $spotlight): void
    {
        // Check if notification should be sent based on session data
        $shouldSendNotification = session('spotlight_send_notification', true);
        
        // Only send notification if the checkbox was enabled and spotlight is published
        if ($shouldSendNotification && $spotlight->is_published) {
            try {
                $this->firebaseService->sendSpotlightNotification($spotlight);
                
                Log::info('Push notification sent for new spotlight', [
                    'spotlight_id' => $spotlight->id,
                    'spotlight_title' => $spotlight->name
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send push notification for new spotlight', [
                    'spotlight_id' => $spotlight->id,
                    'spotlight_title' => $spotlight->name,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Clear the session data after use
        session()->forget('spotlight_send_notification');
    }

    /**
     * Handle the Spotlight "updated" event.
     * Send notification if spotlight was just published.
     */
    public function updated(Spotlight $spotlight): void
    {
        // Check if the spotlight was just published (changed from unpublished to published)
        if ($spotlight->isDirty('is_published') && $spotlight->is_published) {
            // Check if notification should be sent based on session data
            $shouldSendNotification = session('spotlight_send_notification', false);
            
            if ($shouldSendNotification) {
                try {
                    $this->firebaseService->sendSpotlightNotification($spotlight);
                    
                    Log::info('Push notification sent for published spotlight', [
                        'spotlight_id' => $spotlight->id,
                        'spotlight_title' => $spotlight->name
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send push notification for published spotlight', [
                        'spotlight_id' => $spotlight->id,
                        'spotlight_title' => $spotlight->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Clear the session data after use
            session()->forget('spotlight_send_notification');
        }
    }
}
