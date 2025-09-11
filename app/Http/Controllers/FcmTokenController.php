<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FcmTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // Log the incoming FCM token registration request
        Log::info('FCM Token Registration API called', [
            'endpoint' => 'POST /api/v1/fcm-tokens',
            'request_data' => $request->all(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);

        $validated = $request->validate([
            'token' => 'required|string',
            'device_id' => 'nullable|string',
            'platform' => 'nullable|string|in:android,ios',
            'user_id' => 'nullable|integer',
        ]);

        $fcmToken = FcmToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $validated['user_id'] ?? null,
                'device_id' => $validated['device_id'] ?? null,
                'platform' => $validated['platform'] ?? null,
            ]
        );

        Log::info('FCM Token processed successfully', [
            'token_id' => $fcmToken->id,
            'user_id' => $fcmToken->user_id,
            'platform' => $fcmToken->platform,
            'was_updated' => $fcmToken->wasRecentlyCreated ? 'created' : 'updated'
        ]);

        return response()->json(['status' => 'success']);
    }
}
