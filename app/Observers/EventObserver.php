<?php

namespace App\Observers;

use App\Models\Event;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;

class EventObserver
{
    protected $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        $shouldSendNotification = session('event_send_notification', false);

        Log::debug('Event created - checking notification', [
            'event_id' => $event->id,
            'event_title' => $event->title,
            'should_send_notification' => $shouldSendNotification,
            'is_published' => $event->is_published,
            'will_send' => $shouldSendNotification && $event->is_published,
        ]);

        if ($shouldSendNotification && $event->is_published) {
            try {
                $this->firebaseService->sendEventNotification($event);

                Log::info('Push notification sent for new event', [
                    'event_id' => $event->id,
                    'event_title' => $event->title,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send push notification for new event', [
                    'event_id' => $event->id,
                    'event_title' => $event->title,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        session()->forget('event_send_notification');
    }

    /**
     * Handle the Event "updated" event.
     * Send notification if event was just published.
     */
    public function updated(Event $event): void
    {
        Log::debug('Event updated', [
            'event_id' => $event->id,
            'event_title' => $event->title,
            'dirty_fields' => array_keys($event->getDirty()),
            'is_published_dirty' => $event->isDirty('is_published'),
            'current_is_published' => $event->is_published,
            'original_is_published' => $event->getOriginal('is_published'),
        ]);

        if ($event->isDirty('is_published') && $event->is_published) {
            $shouldSendNotification = session('event_send_notification', false);

            Log::debug('Event updated - checking notification for publication', [
                'event_id' => $event->id,
                'event_title' => $event->title,
                'should_send_notification' => $shouldSendNotification,
                'is_published' => $event->is_published,
                'will_send' => $shouldSendNotification,
            ]);

            if ($shouldSendNotification) {
                try {
                    $this->firebaseService->sendEventNotification($event);

                    Log::info('Push notification sent for published event', [
                        'event_id' => $event->id,
                        'event_title' => $event->title,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send push notification for published event', [
                        'event_id' => $event->id,
                        'event_title' => $event->title,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            session()->forget('event_send_notification');
        }
    }
}
