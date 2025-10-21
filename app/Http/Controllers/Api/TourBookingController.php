<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TourBookingResource;
use App\Models\Tour;
use App\Models\TourBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\WhishPaymentService;

class TourBookingController extends Controller
{
    /**
     * Create a new tour booking (public endpoint).
     */
    public function store(Request $request, $tourId)
    {
        $validator = Validator::make($request->all(), [
            'selected_date' => 'required|date|after_or_equal:today',
            'adults' => 'required|integer|min:1',
            'kids' => 'nullable|integer|min:0',
            'infants' => 'nullable|integer|min:0',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:50',
            'special_requests' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tour = Tour::active()->findOrFail($tourId);

        $selectedDate = Carbon::parse($request->selected_date)->format('Y-m-d');
        $dow = strtolower(Carbon::parse($selectedDate)->format('l'));

        // Validate weekday availability
        $isAvailableDay = $tour->dayAvailabilities()->where('day', $dow)->exists();
        if (!$isAvailableDay) {
            return response()->json(['message' => 'Tour is not available on the selected day of the week.'], 422);
        }

        // Validate within date ranges if defined
        $hasRanges = $tour->dateRanges()->exists();
        if ($hasRanges) {
            $withinRange = $tour->dateRanges()
                ->where('start_date', '<=', $selectedDate)
                ->where('end_date', '>=', $selectedDate)
                ->exists();
            if (!$withinRange) {
                return response()->json(['message' => 'Selected date is outside of the tour availability ranges.'], 422);
            }
        }

        $adults = (int) $request->input('adults');
        $kids = (int) $request->input('kids', 0);
        $infants = (int) $request->input('infants', 0);
        $occupancy = $adults + $kids; // infants do not consume capacity by default

        $booking = DB::transaction(function () use ($tour, $selectedDate, $occupancy, $adults, $kids, $infants, $request) {
            // Lock the tour row to serialize capacity checks across concurrent requests
            DB::table('tours')->where('id', $tour->id)->lockForUpdate()->first();
            // Lock existing bookings for the date to prevent overbooking
            $existing = DB::table('tour_bookings')
                ->where('tour_id', $tour->id)
                ->where('selected_date', $selectedDate)
                ->whereIn('status', ['pending', 'confirmed'])
                ->lockForUpdate()
                ->get();

            $booked = 0;
            foreach ($existing as $row) {
                $booked += (int)$row->adults + (int)$row->kids;
            }

            if (!is_null($tour->capacity)) {
                if ($booked + $occupancy > (int)$tour->capacity) {
                    throw new HttpResponseException(response()->json(['message' => 'Not enough capacity for the selected date.'], 422));
                }
            }

            // Compute total price
            $adultPrice = (float) $tour->price;
            $kidsPrice = is_null($tour->kids_price) ? $adultPrice : (float)$tour->kids_price;
            $infantPrice = is_null($tour->infant_price) ? 0.0 : (float)$tour->infant_price;
            $total = ($adults * $adultPrice) + ($kids * $kidsPrice) + ($infants * $infantPrice);

            // Generate unique booking number
            do {
                $bookingNumber = 'TB-' . date('ymd') . '-' . strtoupper(Str::random(6));
            } while (TourBooking::where('booking_number', $bookingNumber)->exists());

            $booking = new TourBooking([
                'booking_number' => $bookingNumber,
                'tour_id' => $tour->id,
                'user_id' => Auth::id(),
                'selected_date' => $selectedDate,
                'adults' => $adults,
                'kids' => $kids,
                'infants' => $infants,
                'total_price' => $total,
                'status' => 'pending',
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'special_requests' => $request->input('special_requests'),
            ]);
            $booking->save();

            return $booking;
        });

        return new TourBookingResource($booking->load('tour'));
    }

    /**
     * List bookings for the authenticated user.
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->per_page ?? 10);
        $bookings = TourBooking::with('tour')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return TourBookingResource::collection($bookings);
    }

    /**
     * Show a specific booking for the authenticated user.
     */
    public function show($id)
    {
        $booking = TourBooking::with('tour')->where('user_id', Auth::id())->findOrFail($id);
        return new TourBookingResource($booking);
    }

    /**
     * Cancel a booking for the authenticated user.
     */
    public function cancel($id)
    {
        $booking = TourBooking::where('user_id', Auth::id())->findOrFail($id);

        if ($booking->status === 'canceled') {
            return response()->json(['message' => 'Booking is already canceled.'], 422);
        }

        if (Carbon::parse($booking->selected_date)->lt(Carbon::today())) {
            return response()->json(['message' => 'Cannot cancel a past booking.'], 422);
        }

        $booking->status = 'canceled';
        $booking->save();

        return new TourBookingResource($booking->load('tour'));
    }

    /**
     * Public: Get booking status by booking number.
     */
    public function status($bookingNumber)
    {
        $booking = TourBooking::with('tour')->where('booking_number', $bookingNumber)->firstOrFail();
        return new TourBookingResource($booking);
    }

    /**
     * Unified payment link generation (public): by booking number, no JWT required.
     */
    public function publicPaymentLink(Request $request, $bookingNumber, WhishPaymentService $whish)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'nullable|string|in:LBP,USD,AED',
            'success_callback_url' => 'nullable|url',
            'failure_callback_url' => 'nullable|url',
            'success_redirect_url' => 'nullable|url',
            'failure_redirect_url' => 'nullable|url',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $booking = TourBooking::with('tour')->where('booking_number', $bookingNumber)->firstOrFail();
        if ($booking->status === 'confirmed') {
            return response()->json(['message' => 'Booking already confirmed.'], 422);
        }

        $currency = $request->input('currency');
        $invoice = 'Tour booking #' . $booking->booking_number;

        // Create unique externalId by appending timestamp to booking ID
        // This ensures each payment attempt has a unique identifier
        $uniqueExternalId = $booking->id . '-' . time();

        $url = $whish->createPaymentLink(
            (float) $booking->total_price,
            $currency,
            $invoice,
            $uniqueExternalId,
            $request->input('success_callback_url'),
            $request->input('failure_callback_url'),
            $request->input('success_redirect_url'),
            $request->input('failure_redirect_url')
        );

        return response()->json(['whish_url' => $url]);
    }

    public function guestPaymentLink(Request $request, WhishPaymentService $whish)
    {
        $validator = Validator::make($request->all(), [
            'booking_number' => 'required|string',
            'email' => 'required|email',
            'currency' => 'nullable|string|in:LBP,USD,AED',
            'success_callback_url' => 'nullable|url',
            'failure_callback_url' => 'nullable|url',
            'success_redirect_url' => 'nullable|url',
            'failure_redirect_url' => 'nullable|url',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $booking = TourBooking::with('tour')->where('booking_number', $request->booking_number)->firstOrFail();
        if (strcasecmp($booking->email, $request->email) !== 0) {
            return response()->json(['message' => 'Email does not match booking.'], 403);
        }

        $currency = $request->input('currency');
        $invoice = 'Tour booking #' . $booking->booking_number;

        $url = $whish->createPaymentLink(
            (float) $booking->total_price,
            $currency,
            $invoice,
            $booking->id,
            $request->input('success_callback_url'),
            $request->input('failure_callback_url'),
            $request->input('success_redirect_url'),
            $request->input('failure_redirect_url')
        );

        return response()->json(['whish_url' => $url]);
    }

    /**
     * Create a Whish payment link for a booking (authenticated user).
     */
    public function createPaymentLink(Request $request, $id, WhishPaymentService $whish)
    {
        $booking = TourBooking::with('tour')->where('user_id', Auth::id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'currency' => 'nullable|string|in:LBP,USD,AED',
            'success_callback_url' => 'nullable|url',
            'failure_callback_url' => 'nullable|url',
            'success_redirect_url' => 'nullable|url',
            'failure_redirect_url' => 'nullable|url',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $currency = $request->input('currency');
        $invoice = 'Tour booking #' . $booking->booking_number;

        // Create unique externalId by appending timestamp to booking ID
        // This ensures each payment attempt has a unique identifier
        $uniqueExternalId = $booking->id . '-' . time();

        $url = $whish->createPaymentLink(
            (float) $booking->total_price,
            $currency,
            $invoice,
            $uniqueExternalId,
            $request->input('success_callback_url'),
            $request->input('failure_callback_url'),
            $request->input('success_redirect_url'),
            $request->input('failure_redirect_url')
        );

        return response()->json(['whish_url' => $url]);
    }

    /**
     * Poll the Whish collect status for a booking (authenticated user).
     * If status is 'success', mark booking as 'confirmed'.
     */
    public function paymentStatus(Request $request, $id, WhishPaymentService $whish)
    {
        $booking = TourBooking::where('user_id', Auth::id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'currency' => 'nullable|string|in:LBP,USD,AED',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $currency = $request->input('currency');
        $status = $whish->getCollectStatus($currency ?: config('whish.default_currency', 'USD'), $booking->id);

        if ($status === 'success' && $booking->status !== 'confirmed') {
            $booking->status = 'confirmed';
            $booking->save();
        }

        return response()->json(['collect_status' => $status, 'booking_status' => $booking->status]);
    }
}
