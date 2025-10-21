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
        // Log incoming callback request
        Log::info('[WhishCallback] Incoming request', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'query_params' => $request->query(),
            'user_agent' => $request->header('User-Agent'),
            'ip' => $request->ip(),
        ]);

        // Extract externalId from query parameters
        $externalId = $request->query('externalId');

        if (empty($externalId)) {
            Log::warning('[WhishCallback] Missing externalId', [
                'query_params' => $request->query(),
            ]);
            return response()->json(['message' => 'externalId is required'], 422);
        }

        // Extract booking ID from compound externalId (format: "bookingId-timestamp")
        $bookingId = (int) explode('-', $externalId)[0];

        Log::info('[WhishCallback] Processing callback for booking', [
            'externalId' => $externalId,
            'bookingId' => $bookingId,
        ]);

        // Find the booking by extracted booking ID
        $booking = TourBooking::find($bookingId);
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
        ]);

        try {
            // Poll Whish for this booking's collect status using the original externalId
            $currency = config('whish.default_currency', 'USD');
            
            Log::info('[WhishCallback] Checking Whish collect status', [
                'booking_id' => $booking->id,
                'original_externalId' => $externalId,
                'currency' => $currency,
            ]);

            $status = $whish->getCollectStatus($currency, $externalId);
            
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
