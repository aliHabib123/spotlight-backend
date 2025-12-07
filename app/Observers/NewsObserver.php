<?php

namespace App\Observers;

use App\Models\News;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;

class NewsObserver
{
    protected $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Handle the News "created" event.
     */
    public function created(News $news): void
    {
        $shouldSendNotification = session('news_send_notification', false);

        Log::debug('News created - checking notification', [
            'news_id' => $news->id,
            'news_title' => $news->title,
            'should_send_notification' => $shouldSendNotification,
            'is_published' => $news->is_published,
            'will_send' => $shouldSendNotification && $news->is_published,
        ]);

        if ($shouldSendNotification && $news->is_published) {
            try {
                $this->firebaseService->sendNewsNotification($news);

                Log::info('Push notification sent for new news item', [
                    'news_id' => $news->id,
                    'news_title' => $news->title,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send push notification for new news item', [
                    'news_id' => $news->id,
                    'news_title' => $news->title,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        session()->forget('news_send_notification');
    }

    /**
     * Handle the News "updated" event.
     * Send notification if news was just published.
     */
    public function updated(News $news): void
    {
        Log::debug('News updated', [
            'news_id' => $news->id,
            'news_title' => $news->title,
            'dirty_fields' => array_keys($news->getDirty()),
            'is_published_dirty' => $news->isDirty('is_published'),
            'current_is_published' => $news->is_published,
            'original_is_published' => $news->getOriginal('is_published'),
        ]);

        if ($news->isDirty('is_published') && $news->is_published) {
            $shouldSendNotification = session('news_send_notification', false);

            Log::debug('News updated - checking notification for publication', [
                'news_id' => $news->id,
                'news_title' => $news->title,
                'should_send_notification' => $shouldSendNotification,
                'is_published' => $news->is_published,
                'will_send' => $shouldSendNotification,
            ]);

            if ($shouldSendNotification) {
                try {
                    $this->firebaseService->sendNewsNotification($news);

                    Log::info('Push notification sent for published news item', [
                        'news_id' => $news->id,
                        'news_title' => $news->title,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send push notification for published news item', [
                        'news_id' => $news->id,
                        'news_title' => $news->title,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            session()->forget('news_send_notification');
        }
    }
}
