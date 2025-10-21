<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TourBooking;
use App\Services\WhishPaymentService;
use Illuminate\Http\Request;

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
        $externalId = $request->query('externalId')
            ?? $request->query('external_id')
            ?? $request->query('externalid');

        if (empty($externalId)) {
            return response()->json(['message' => 'externalId is required'], 422);
        }

        $currency = $request->query('currency', config('whish.default_currency', 'USD'));

        $booking = TourBooking::find((int) $externalId);
        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        // Poll Whish for authoritative collect status
        $status = $whish->getCollectStatus($currency, $booking->id);

        if ($status === 'success' && $booking->status !== 'confirmed') {
            $booking->status = 'confirmed';
            $booking->save();
        } elseif ($status === 'failed' && $booking->status !== 'canceled') {
            $booking->status = 'canceled';
            $booking->save();
        }

        return response()->json([
            'booking_id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'collect_status' => $status,
            'booking_status' => $booking->status,
        ]);
    }
}
