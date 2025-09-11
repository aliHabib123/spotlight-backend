<?php

namespace App\Console\Commands;

use App\Models\Spotlight;
use App\Services\FirebaseNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test
                            {type : Type of entity (spotlight)}
                            {id : ID of the entity}
                            {token : FCM token to send notification to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification for spotlights';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $id = $this->argument('id');
        $token = $this->argument('token');
        
        if (empty($token)) {
            $this->error('FCM token is required');
            return 1;
        }
        
        $this->info("Starting test notification process...");
        $this->info("FCM token: " . substr($token, 0, 10) . "..." . substr($token, -10));
        
        try {
            // Get the record based on type and ID
            $record = null;
            switch ($type) {
                case 'spotlight':
                    $record = Spotlight::find($id);
                    break;
                default:
                    $this->error("Invalid type. Must be 'spotlight'.");
                    return 1;
            }
            
            if (!$record) {
                $this->error("No {$type} found with ID {$id}");
                return 1;
            }
            
            $this->info("Found {$type} with ID {$id}: " . $record->name);
            
            // Check Firebase config
            if (empty(config('firebase.project_id')) || empty(config('firebase.credentials'))) {
                $this->error("Firebase not properly configured");
                $this->line("Project ID exists: " . (empty(config('firebase.project_id')) ? 'No' : 'Yes'));
                $this->line("Credentials path exists: " . (empty(config('firebase.credentials')) ? 'No' : 'Yes'));
                return 1;
            }
            
            // Check credentials file exists
            $credentialsPath = config('firebase.credentials');
            if (!file_exists($credentialsPath)) {
                $this->error("Firebase credentials file not found: {$credentialsPath}");
                return 1;
            }
            
            $this->info("Firebase configuration validated");
            
            // Initialize Firebase Service
            $firebaseService = app(FirebaseNotificationService::class);
            
            if (!$firebaseService->isEnabled()) {
                $this->error("Firebase service is not enabled");
                return 1;
            }
            
            $this->info("Firebase service initialized");
            
            // Create notification content for spotlight
            $title = 'Test: New Spotlight Available!';
            $body = "Check out: {$record->name}";
            $notificationData = [
                'type' => 'spotlight',
                'spotlight_id' => (string) $record->id,
                'title' => $record->name,
                'category' => $record->category->name ?? 'General',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'test' => 'true'
            ];
            
            $this->info("Created spotlight notification payload");
            
            // Show notification details
            $this->line("Title: {$title}");
            $this->line("Body: {$body}");
            $this->line("Data: " . json_encode($notificationData));
            
            $this->info("Sending notification to FCM token...");
            
            // Send to the specified token
            $result = $firebaseService->sendToTokens([$token], $title, $body, $notificationData);
            
            $this->info("Received response from Firebase");
            
            if ($result) {
                $successCount = $result->successes()->count();
                $failureCount = $result->failures()->count();
                
                $this->line("Successful deliveries: {$successCount}");
                $this->line("Failed deliveries: {$failureCount}");
                
                if ($successCount > 0) {
                    $this->info("🎉 Notification delivered successfully!");
                    Log::info('Test notification sent via CLI', [
                        'token' => substr($token, 0, 20) . '...',
                        'spotlight_id' => $record->id,
                        'type' => $type,
                        'success_count' => $successCount
                    ]);
                    return 0;
                } else {
                    $failures = $result->failures();
                    $errorMsg = 'Unknown error';
                    if ($failures->count() > 0) {
                        $firstFailure = $failures->getItems()[0];
                        $errorMsg = $firstFailure->error()->getMessage();
                    }
                    
                    $this->error("❌ Firebase error: {$errorMsg}");
                    Log::error('Test notification failed via CLI', [
                        'error' => $errorMsg,
                        'token' => substr($token, 0, 20) . '...',
                        'failure_count' => $failureCount
                    ]);
                    return 1;
                }
            } else {
                $this->error("❌ No response from Firebase service");
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            $this->line("Exception class: " . get_class($e));
            $this->line("Stack trace: " . $e->getTraceAsString());
            
            Log::error('Test notification error via CLI', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
