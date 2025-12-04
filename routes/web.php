<?php

use Illuminate\Support\Facades\Route;
use App\Services\FirebaseNotificationService;
use App\Models\FcmToken;
use Illuminate\Http\Request;
use Laravel\Octane\Facades\Octane;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/octane-test', function () {
    static $counter = 0;
    $counter++;
    return "Counter: {$counter}";
});
Route::get('/octane-test1', function () {
    $table = Octane::table('counter');
    $table['value'] = ($table['value'] ?? 0) + 1;
    return 'Counter: '.$table['value'];
});

// Simple payment result pages (for user-facing redirects)
Route::get('/payment/success', function (Request $request) {
    return view('payment.success', [
        'booking_number' => $request->query('bookingNumber'),
        'status' => $request->query('status', 'success'),
    ]);
});

Route::get('/payment/failure', function (Request $request) {
    return view('payment.failure', [
        'booking_number' => $request->query('bookingNumber'),
        'status' => $request->query('status', 'failed'),
    ]);
});

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
