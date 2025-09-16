<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class TestImageProcessing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:image-processing {image_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Intervention Image package functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $imagePath = $this->argument('image_path');
        
        $this->info("=== Testing Intervention Image Package ===");
        
        // Check PHP extensions
        $this->info("1. Checking PHP extensions:");
        $this->info("   - GD: " . (extension_loaded('gd') ? '✓ Available' : '✗ Not available'));
        $this->info("   - Imagick: " . (extension_loaded('imagick') ? '✓ Available' : '✗ Not available'));
        
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            $this->error("Neither GD nor Imagick extension is available!");
            return Command::FAILURE;
        }
        
        // Check memory and time limits
        $this->info("2. System limits:");
        $this->info("   - Memory limit: " . ini_get('memory_limit'));
        $this->info("   - Max execution time: " . ini_get('max_execution_time'));
        
        // Check if image file exists
        $this->info("3. Checking image file:");
        $fullPath = Storage::disk('public')->path($imagePath);
        $this->info("   - Full path: {$fullPath}");
        
        if (!file_exists($fullPath)) {
            $this->error("Image file does not exist!");
            return Command::FAILURE;
        }
        
        $fileSize = filesize($fullPath);
        $this->info("   - File size: " . number_format($fileSize) . " bytes (" . round($fileSize / 1024 / 1024, 2) . " MB)");
        
        // Test Intervention Image
        $this->info("4. Testing Intervention Image:");
        
        try {
            $this->info("   - Initializing ImageManager...");
            $manager = new ImageManager(new Driver());
            $this->info("   ✓ ImageManager initialized successfully");
            
            $this->info("   - Reading image...");
            $image = $manager->read($fullPath);
            $this->info("   ✓ Image read successfully");
            $this->info("   - Original dimensions: {$image->width()}x{$image->height()}");
            
            $this->info("   - Testing clone operation...");
            $clonedImage = clone $image;
            $this->info("   ✓ Clone operation successful");
            
            $this->info("   - Testing scaleDown operation...");
            $startTime = microtime(true);
            $clonedImage->scaleDown(width: 1200);
            $endTime = microtime(true);
            $processingTime = round($endTime - $startTime, 2);
            
            $this->info("   ✓ ScaleDown operation successful");
            $this->info("   - New dimensions: {$clonedImage->width()}x{$clonedImage->height()}");
            $this->info("   - Processing time: {$processingTime} seconds");
            
            $this->info("   - Testing save operation...");
            $testPath = Storage::disk('public')->path('test_thumbnail.jpg');
            $clonedImage->toJpeg(85)->save($testPath);
            
            if (file_exists($testPath)) {
                $this->info("   ✓ Save operation successful");
                $this->info("   - Test file size: " . number_format(filesize($testPath)) . " bytes");
                unlink($testPath); // Clean up
                $this->info("   ✓ Test file cleaned up");
            } else {
                $this->error("   ✗ Save operation failed - file not created");
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("   ✗ Error during image processing: " . $e->getMessage());
            $this->error("   Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        } catch (\Error $e) {
            $this->error("   ✗ Fatal error during image processing: " . $e->getMessage());
            $this->error("   Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
        
        $this->info("=== All tests passed! Intervention Image is working correctly ===");
        return Command::SUCCESS;
    }
}
