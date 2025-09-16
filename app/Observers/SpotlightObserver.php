<?php

namespace App\Observers;

use App\Models\Spotlight;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

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
        // Generate thumbnails if featured image is present
        if ($spotlight->featured_image) {
            $this->generateThumbnails($spotlight);
        }

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
        // Generate thumbnails if featured image was changed
        if ($spotlight->isDirty('featured_image') && $spotlight->featured_image) {
            $this->generateThumbnails($spotlight);
        }

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

    /**
     * Generate thumbnails for a spotlight.
     */
    private function generateThumbnails(Spotlight $spotlight): void
    {
        try {
            // Set memory limit for image processing
            ini_set('memory_limit', '512M');
            set_time_limit(300);

            // Check if featured image exists
            if (!Storage::disk('public')->exists($spotlight->featured_image)) {
                Log::warning('Featured image not found for thumbnail generation', [
                    'spotlight_id' => $spotlight->id,
                    'featured_image' => $spotlight->featured_image
                ]);
                return;
            }

            // Initialize image manager
            $manager = new ImageManager(new Driver());

            // Get the original image
            $imagePath = Storage::disk('public')->path($spotlight->featured_image);
            $originalImage = $manager->read($imagePath);

            // Step 1: Create base thumbnail resized to 1200px width (proportional height)
            $baseThumbnail = clone $originalImage;
            $baseThumbnail->scaleDown(width: 1200);
            
            // Save base thumbnail
            $baseThumbnailPath = $this->saveThumbnail($baseThumbnail, $spotlight->id, 'base');

            // Step 2: Generate small thumbnail (25% of original)
            $smallThumbnail = clone $originalImage;
            $newWidth = max(1, (int)($originalImage->width() * 0.25));
            $newHeight = max(1, (int)($originalImage->height() * 0.25));
            $smallThumbnail->resize($newWidth, $newHeight);
            $thumbnailSmallPath = $this->saveThumbnail($smallThumbnail, $spotlight->id, 'small');

            // Step 3: Generate specific thumbnails from the base thumbnail
            $thumbnail1200x360 = $this->generateSpecificThumbnail($baseThumbnail, 1200, 360, $spotlight->id, '1200x360');
            $thumbnail1080x1080 = $this->generateSpecificThumbnail($baseThumbnail, 1080, 1080, $spotlight->id, '1080x1080');

            // Update spotlight with all thumbnail paths
            $spotlight->updateQuietly([
                'thumbnail' => $baseThumbnailPath,
                'thumbnail_1200x360' => $thumbnail1200x360,
                'thumbnail_1080x1080' => $thumbnail1080x1080,
                'thumbnail_small' => $thumbnailSmallPath,
            ]);

            Log::info('Thumbnails generated successfully', [
                'spotlight_id' => $spotlight->id,
                'thumbnails' => [$baseThumbnailPath, $thumbnail1200x360, $thumbnail1080x1080, $thumbnailSmallPath]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate thumbnails', [
                'spotlight_id' => $spotlight->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Save a thumbnail image to disk.
     */
    private function saveThumbnail($image, int $spotlightId, string $size): string
    {
        // Generate filename
        $extension = 'jpg';
        $filename = "thumbnails/spotlight_{$spotlightId}_{$size}.{$extension}";
        
        // Ensure thumbnails directory exists
        $thumbnailsDir = Storage::disk('public')->path('thumbnails');
        if (!is_dir($thumbnailsDir)) {
            mkdir($thumbnailsDir, 0755, true);
        }

        // Save the thumbnail
        $fullPath = Storage::disk('public')->path($filename);
        $image->toJpeg(85)->save($fullPath);

        return $filename;
    }

    /**
     * Generate a specific thumbnail from the base resized image.
     */
    private function generateSpecificThumbnail($baseImage, int $width, int $height, int $spotlightId, string $size): string
    {
        // Clone the base image to avoid modifying it
        $thumbnail = clone $baseImage;

        // Crop from center to get the specific dimensions
        $thumbnail->crop($width, $height);

        // Save the thumbnail
        return $this->saveThumbnail($thumbnail, $spotlightId, $size);
    }
}
