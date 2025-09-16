<?php

namespace App\Console\Commands;

use App\Models\Spotlight;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class GenerateSpotlightThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spotlight:generate-thumbnails 
                            {--force : Force regeneration of existing thumbnails}
                            {--id= : Generate thumbnails for specific spotlight ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate thumbnails for spotlight featured images (1200x360 and 1080x1080)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Set memory limit for image processing
        ini_set('memory_limit', '512M');
        set_time_limit(300);
        
        $this->info('Starting thumbnail generation for spotlights...');
        $this->info('Memory limit set to: ' . ini_get('memory_limit'));

        // Get spotlights to process
        $query = Spotlight::whereNotNull('featured_image');
        
        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        }

        $spotlights = $query->get();

        if ($spotlights->isEmpty()) {
            $this->warn('No spotlights found with featured images.');
            return Command::SUCCESS;
        }

        $this->info("Found {$spotlights->count()} spotlights to process.");

        $progressBar = $this->output->createProgressBar($spotlights->count());
        $progressBar->start();

        $processed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($spotlights as $spotlight) {
            try {
                $this->info("Processing spotlight ID: {$spotlight->id}");
                $this->info("Featured image: {$spotlight->featured_image}");
                
                $result = $this->generateThumbnailsForSpotlight($spotlight);
                
                if ($result['processed']) {
                    $processed++;
                    $this->info("✓ Successfully processed spotlight {$spotlight->id}");
                } else {
                    $skipped++;
                    $this->warn("⚠ Skipped spotlight {$spotlight->id}: {$result['reason']}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("✗ Error processing spotlight {$spotlight->id}: " . $e->getMessage());
                $this->error("Stack trace: " . $e->getTraceAsString());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Thumbnail generation completed!");
        $this->info("Processed: {$processed}");
        $this->info("Skipped: {$skipped}");
        if ($errors > 0) {
            $this->error("Errors: {$errors}");
        }

        return Command::SUCCESS;
    }

    /**
     * Generate thumbnails for a specific spotlight.
     */
    private function generateThumbnailsForSpotlight(Spotlight $spotlight): array
    {
        $this->info("  → Checking existing thumbnails...");
        $force = $this->option('force');
        
        // Check if thumbnails already exist and force is not set
        if (!$force && $spotlight->thumbnail && $spotlight->thumbnail_1200x360 && $spotlight->thumbnail_1080x1080) {
            $this->info("  → Thumbnails already exist, skipping");
            return ['processed' => false, 'reason' => 'thumbnails_exist'];
        }

        $this->info("  → Checking featured image exists...");
        // Check if featured image exists
        if (!$spotlight->featured_image) {
            throw new \Exception("No featured image set for spotlight");
        }

        if (!Storage::disk('public')->exists($spotlight->featured_image)) {
            throw new \Exception("Featured image file not found: {$spotlight->featured_image}");
        }

        $this->info("  → Initializing image manager...");
        // Initialize image manager with error handling
        try {
            $manager = new ImageManager(new Driver());
        } catch (\Exception $e) {
            throw new \Exception("Failed to initialize image manager: " . $e->getMessage());
        }

        $this->info("  → Reading original image...");
        // Get the original image
        $imagePath = Storage::disk('public')->path($spotlight->featured_image);
        $this->info("  → Image path: {$imagePath}");
        
        if (!file_exists($imagePath)) {
            throw new \Exception("Image file does not exist at path: {$imagePath}");
        }

        try {
            $originalImage = $manager->read($imagePath);
            $this->info("  → Original image dimensions: {$originalImage->width()}x{$originalImage->height()}");
        } catch (\Exception $e) {
            throw new \Exception("Failed to read image: " . $e->getMessage());
        }

        $this->info("  → Creating base thumbnail (1200px width)...");
        // Step 1: Create base thumbnail resized to 1200px width (proportional height)
        try {
            $baseThumbnail = clone $originalImage;
            $baseThumbnail->scaleDown(width: 1200);
            $this->info("  → Base thumbnail dimensions: {$baseThumbnail->width()}x{$baseThumbnail->height()}");
        } catch (\Exception $e) {
            throw new \Exception("Failed to create base thumbnail: " . $e->getMessage());
        }
        
        $this->info("  → Saving base thumbnail...");
        // Save base thumbnail
        $baseThumbnailPath = $this->saveThumbnail($baseThumbnail, $spotlight->id, 'base');

        $this->info("  → Generating small thumbnail (25% of original)...");
        // Step 2: Generate small thumbnail (25% of original)
        $smallThumbnail = clone $originalImage;
        $newWidth = max(1, (int)($originalImage->width() * 0.25));
        $newHeight = max(1, (int)($originalImage->height() * 0.25));
        $smallThumbnail->resize($newWidth, $newHeight);
        $thumbnailSmallPath = $this->saveThumbnail($smallThumbnail, $spotlight->id, 'small');

        $this->info("  → Generating 1200x360 thumbnail...");
        // Step 3: Generate specific thumbnails from the base thumbnail
        $thumbnail1200x360 = $this->generateSpecificThumbnail($baseThumbnail, 1200, 360, $spotlight->id, '1200x360');
        
        $this->info("  → Generating 1080x1080 thumbnail...");
        $thumbnail1080x1080 = $this->generateSpecificThumbnail($baseThumbnail, 1080, 1080, $spotlight->id, '1080x1080');

        $this->info("  → Updating database...");
        // Update spotlight with all thumbnail paths
        $spotlight->update([
            'thumbnail' => $baseThumbnailPath,
            'thumbnail_1200x360' => $thumbnail1200x360,
            'thumbnail_1080x1080' => $thumbnail1080x1080,
            'thumbnail_small' => $thumbnailSmallPath,
        ]);

        $this->info("  → Completed successfully!");
        return ['processed' => true];
    }

    /**
     * Save a thumbnail image to disk.
     */
    private function saveThumbnail($image, int $spotlightId, string $size): string
    {
        $this->info("    → Saving {$size} thumbnail...");
        
        // Generate filename
        $extension = 'jpg'; // Convert all thumbnails to JPG for consistency
        $filename = "thumbnails/spotlight_{$spotlightId}_{$size}.{$extension}";
        
        // Ensure thumbnails directory exists
        $thumbnailsDir = Storage::disk('public')->path('thumbnails');
        $this->info("    → Thumbnails directory: {$thumbnailsDir}");
        
        if (!is_dir($thumbnailsDir)) {
            $this->info("    → Creating thumbnails directory...");
            if (!mkdir($thumbnailsDir, 0755, true)) {
                throw new \Exception("Failed to create thumbnails directory: {$thumbnailsDir}");
            }
        }

        // Check directory permissions
        if (!is_writable($thumbnailsDir)) {
            throw new \Exception("Thumbnails directory is not writable: {$thumbnailsDir}");
        }

        // Save the thumbnail
        $fullPath = Storage::disk('public')->path($filename);
        $this->info("    → Saving to: {$fullPath}");
        
        try {
            $image->toJpeg(85)->save($fullPath);
            
            // Verify file was created
            if (!file_exists($fullPath)) {
                throw new \Exception("File was not created after save operation");
            }
            
            $fileSize = filesize($fullPath);
            $this->info("    → File saved successfully, size: {$fileSize} bytes");
            
        } catch (\Exception $e) {
            throw new \Exception("Failed to save thumbnail: " . $e->getMessage());
        }

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
