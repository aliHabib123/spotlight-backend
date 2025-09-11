<?php

use Illuminate\Support\Facades\Route;
use App\Services\FirebaseNotificationService;
use App\Models\FcmToken;

Route::get('/test-firebase', function () {
    $firebaseService = app(FirebaseNotificationService::class);
    
    if (!$firebaseService->isEnabled()) {
        return response()->json(['error' => 'Firebase service not enabled']);
    }
    
    $tokens = FcmToken::pluck('token')->toArray();
    
    if (empty($tokens)) {
        return response()->json(['error' => 'No FCM tokens found']);
    }
    
    try {
        $result = $firebaseService->sendToAllDevices(
            'Test Notification',
            'This is a test from Laravel',
            ['test' => 'true']
        );
        
        return response()->json([
            'success' => true,
            'tokens_sent_to' => count($tokens),
            'firebase_result' => [
                'successful' => $result->successes()->count(),
                'failed' => $result->failures()->count()
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Firebase error: ' . $e->getMessage()
        ]);
    }
});
