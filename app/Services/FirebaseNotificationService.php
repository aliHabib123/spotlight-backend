<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class FirebaseNotificationService
{
    protected $messaging;
    protected $enabled;

    public function __construct()
    {
        $this->enabled = Config::get('firebase.fcm.enabled', true);

        if ($this->enabled) {
            try {
                $serviceAccountPath = Config::get('firebase.service_account.path');

                if (!file_exists($serviceAccountPath)) {
                    Log::error('Firebase service account file not found', ['path' => $serviceAccountPath]);
                    $this->enabled = false;
                    return;
                }

                $factory = (new Factory)->withServiceAccount($serviceAccountPath);
                $this->messaging = $factory->createMessaging();

                Log::info('Firebase messaging service initialized successfully');
            } catch (\Exception $e) {
                Log::error('Failed to initialize Firebase messaging service', ['error' => $e->getMessage()]);
                $this->enabled = false;
            }
        }
    }

    /**
     * Check if FCM is enabled and properly configured
     */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->messaging !== null;
    }

    /**
     * Send notification to all registered devices
     */
    public function sendToAllDevices(string $title, string $body, array $data = [])
    {
        if (!$this->isEnabled()) {
            Log::warning('FCM is disabled or not properly configured');
            return null;
        }

        $tokens = FcmToken::pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info('No FCM tokens found to send notifications');
            return null;
        }

        // Log the tokens being used for debugging
        Log::info('Sending notifications to tokens', [
            'token_count' => count($tokens),
            'tokens' => array_map(function($token) {
                return substr($token, 0, 20) . '...'; // Log partial tokens for security
            }, $tokens)
        ]);

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send notification to specific user
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = [])
    {
        if (!$this->isEnabled()) {
            Log::warning('FCM is disabled or not properly configured');
            return null;
        }

        $tokens = FcmToken::where('user_id', $userId)->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info("No FCM tokens found for user {$userId}");
            return null;
        }

        return $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send notification to specific tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [])
    {
        if (!$this->isEnabled()) {
            Log::warning('FCM is disabled or not properly configured');
            return null;
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data);

            $report = $this->messaging->sendMulticast($message, $tokens);

            Log::info('FCM notification sent', [
                'successful' => $report->successes()->count(),
                'failed' => $report->failures()->count(),
                'title' => $title,
                'body' => $body,
                'total_tokens' => count($tokens)
            ]);

            // Remove invalid tokens
            if ($report->hasFailures()) {
                $this->removeInvalidTokens($report->failures());
            }

            return $report;

        } catch (\Exception $e) {
            Log::error('Failed to send FCM notification', [
                'error' => $e->getMessage(),
                'title' => $title,
                'body' => $body,
                'token_count' => count($tokens)
            ]);

            throw $e;
        }
    }

    /**
     * Send spotlight notification to all devices
     */
    public function sendSpotlightNotification($spotlight)
    {
        $title = Config::get('firebase.fcm.new_spotlight_title', 'New Spotlight Available!');
        $body = "Check out: {$spotlight->name}";
        $data = [
            'type' => 'spotlight',
            'spotlight_id' => (string) $spotlight->id,
            'title' => $spotlight->name,
            'category' => $spotlight->category->name ?? 'General',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ];

        Log::info('Preparing spotlight notification', [
            'spotlight_id' => $spotlight->id,
            'title' => $title,
            'body' => $body,
            'data_payload' => $data
        ]);

        return $this->sendToAllDevices($title, $body, $data);
    }

    /**
     * Send news notification to all devices
     */
    public function sendNewsNotification($news)
    {
        $title = Config::get('firebase.fcm.new_news_title', 'Latest News');
        $body = "Latest news: {$news->title}";
        $data = [
            'type' => 'news',
            'news_id' => (string) $news->id,
            'title' => $news->title,
            'category' => $news->category->name ?? 'General',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        Log::info('Preparing news notification', [
            'news_id' => $news->id,
            'title' => $title,
            'body' => $body,
            'data_payload' => $data,
        ]);

        return $this->sendToAllDevices($title, $body, $data);
    }

    /**
     * Send event notification to all devices
     */
    public function sendEventNotification($event)
    {
        $title = Config::get('firebase.fcm.new_event_title', 'New Event');
        $body = "New event: {$event->title}";
        $data = [
            'type' => 'event',
            'event_id' => (string) $event->id,
            'title' => $event->title,
            'category' => $event->eventCategory->name ?? 'General',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        Log::info('Preparing event notification', [
            'event_id' => $event->id,
            'title' => $title,
            'body' => $body,
            'data_payload' => $data,
        ]);

        return $this->sendToAllDevices($title, $body, $data);
    }

    /**
     * Send tour notification to all devices
     */
    public function sendTourNotification($tour)
    {
        $title = Config::get('firebase.fcm.new_tour_title', 'New Tour Available');
        $body = "Check out this tour: {$tour->title}";
        $data = [
            'type' => 'tour',
            'tour_id' => (string) $tour->id,
            'title' => $tour->title,
            'location' => $tour->location->name ?? 'General',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        Log::info('Preparing tour notification', [
            'tour_id' => $tour->id,
            'title' => $title,
            'body' => $body,
            'data_payload' => $data,
        ]);

        return $this->sendToAllDevices($title, $body, $data);
    }

    /**
     * Remove invalid FCM tokens from database
     */
    protected function removeInvalidTokens($failures)
    {
        $removedCount = 0;

        foreach ($failures as $failure) {
            $token = $failure->target()->value();
            $errorCode = $failure->error()->errorCode();

            // Remove tokens that are invalid or unregistered
            if (in_array($errorCode, ['INVALID_ARGUMENT', 'UNREGISTERED', 'NOT_FOUND'])) {
                $deleted = FcmToken::where('token', $token)->delete();
                if ($deleted) {
                    $removedCount++;
                    Log::info("Removed invalid FCM token", [
                        'token' => substr($token, 0, 20) . '...',
                        'error_code' => $errorCode
                    ]);
                }
            }
        }

        if ($removedCount > 0) {
            Log::info("Cleaned up {$removedCount} invalid FCM tokens");
        }
    }
}
