<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TourBooking;
use App\Services\WhishPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhishCallbackController extends Controller
{
    /**
     * Whish success callback (GET). Delegates to handle() which calls collect/status.
     */
    public function success(Request $request, WhishPaymentService $whish)
    {
        return $this->handle($request, $whish);
    }

    /**
     * Whish failure callback (GET). Delegates to handle() which calls collect/status.
     */
    public function failure(Request $request, WhishPaymentService $whish)
    {
        return $this->handle($request, $whish);
    }

    /**
     * Shared handler: read externalId/currency, poll Whish status, update booking.
     */
    protected function handle(Request $request, WhishPaymentService $whish)
    {
        // Log incoming callback request with ALL possible data
        Log::info('[WhishCallback] Incoming request', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'query_params' => $request->query(),
            'post_data' => $request->all(),
            'raw_body' => $request->getContent(),
            'headers' => [
                'user-agent' => $request->header('User-Agent'),
                'x-forwarded-for' => $request->header('X-Forwarded-For'),
                'remote-addr' => $request->ip(),
                'content-type' => $request->header('Content-Type'),
                'all_headers' => $request->headers->all(),
            ],
        ]);

        // Try to get externalId from query params, POST data, or JSON body
        $externalId = $request->query('externalId')
            ?? $request->query('external_id')
            ?? $request->query('externalid')
            ?? $request->input('externalId')
            ?? $request->input('external_id')
            ?? $request->input('externalid')
            ?? $request->input('booking_id')
            ?? $request->input('id');

        if (empty($externalId)) {
            Log::warning('[WhishCallback] Missing externalId', [
                'query_params' => $request->query(),
                'post_data' => $request->all(),
                'raw_body' => $request->getContent(),
                'tried_keys' => ['externalId', 'external_id', 'externalid', 'booking_id', 'id'],
            ]);
            return response()->json(['message' => 'externalId is required'], 422);
        }

        $currency = $request->query('currency', config('whish.default_currency', 'USD'));

        Log::info('[WhishCallback] Processing callback', [
            'externalId' => $externalId,
            'currency' => $currency,
        ]);

        $booking = TourBooking::find((int) $externalId);
        if (!$booking) {
            Log::error('[WhishCallback] Booking not found', [
                'externalId' => $externalId,
            ]);
            return response()->json(['message' => 'Booking not found'], 404);
        }

        Log::info('[WhishCallback] Found booking', [
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'current_status' => $booking->status,
            'tour_id' => $booking->tour_id,
        ]);

        try {
            // Poll Whish for authoritative collect status
            Log::info('[WhishCallback] Polling Whish collect status', [
                'booking_id' => $booking->id,
                'currency' => $currency,
            ]);
            
            $status = $whish->getCollectStatus($currency, $booking->id);
            
            Log::info('[WhishCallback] Whish collect status received', [
                'booking_id' => $booking->id,
                'collect_status' => $status,
                'current_booking_status' => $booking->status,
            ]);

            $originalStatus = $booking->status;
            $statusUpdated = false;

            if ($status === 'success' && $booking->status !== 'confirmed') {
                $booking->status = 'confirmed';
                $booking->save();
                $statusUpdated = true;
                Log::info('[WhishCallback] Booking confirmed', [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'old_status' => $originalStatus,
                    'new_status' => 'confirmed',
                ]);
            } elseif ($status === 'failed' && $booking->status !== 'canceled') {
                $booking->status = 'canceled';
                $booking->save();
                $statusUpdated = true;
                Log::info('[WhishCallback] Booking canceled', [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'old_status' => $originalStatus,
                    'new_status' => 'canceled',
                ]);
            } else {
                Log::info('[WhishCallback] No status update needed', [
                    'booking_id' => $booking->id,
                    'collect_status' => $status,
                    'current_booking_status' => $booking->status,
                    'reason' => $status === 'pending' ? 'status_pending' : 'already_updated',
                ]);
            }

            $response = [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'collect_status' => $status,
                'booking_status' => $booking->status,
                'status_updated' => $statusUpdated,
            ];

            Log::info('[WhishCallback] Response sent', $response);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('[WhishCallback] Error processing callback', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'error' => 'Failed to process callback',
            ], 500);
        }
    }
}
