<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessReservation;
use App\Models\Reservation;
use App\Models\ReservationSetting;
use App\Models\RestaurantSetting;
use App\Models\Table;
use App\Services\OtpService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private WhatsAppService $whatsAppService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:tables,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'pax' => 'required|integer|min:1|max:20',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $table = Table::findOrFail($data['table_id']);

        // Check if date is open for reservations
        if (!ReservationSetting::isDateOpen($data['reservation_date'])) {
            return response()->json([
                'success' => false,
                'message' => 'Reservations are closed for this date',
            ], 403);
        }

        // Check if table is available
        if (!$table->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'This table is not available',
            ], 409);
        }

        // Check if table already has a reservation at this time
        if ($table->hasReservationAt($data['reservation_date'], $data['reservation_time'])) {
            return response()->json([
                'success' => false,
                'message' => 'This table is already reserved at the selected time',
            ], 409);
        }

        // Check table capacity
        if ($data['pax'] > $table->capacity) {
            return response()->json([
                'success' => false,
                'message' => "This table can only accommodate {$table->capacity} people",
            ], 422);
        }

        // Use Redis lock to prevent race conditions
        $lockKey = "reservation_lock_{$data['table_id']}_{$data['reservation_date']}_{$data['reservation_time']}";
        $lock = Cache::lock($lockKey, 10);

        try {
            if ($lock->get()) {
                // Double-check availability after acquiring lock
                if ($table->hasReservationAt($data['reservation_date'], $data['reservation_time'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This table is already reserved at the selected time',
                    ], 409);
                }

                // Calculate deposit amount
                $depositAmount = RestaurantSetting::calculateDeposit($data['pax']);

                // Create pending reservation first
                $reservation = Reservation::create([
                    'table_id' => $data['table_id'],
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'],
                    'customer_phone' => $data['customer_phone'],
                    'pax' => $data['pax'],
                    'deposit_amount' => $depositAmount,
                    'reservation_date' => $data['reservation_date'],
                    'reservation_time' => $data['reservation_time'],
                    'notes' => $data['notes'] ?? null,
                    'status' => 'pending',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Generate OTP
                $otpData = $this->otpService->generateOtp($data['customer_phone'], $reservation->id);
                
                // Send OTP via WhatsApp
                $this->whatsAppService->sendOtp($data['customer_phone'], $otpData['otp_code'], $data['customer_name']);

                // Update reservation with OTP session ID
                $reservation->update(['otp_session_id' => $otpData['session_id']]);

                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent to your WhatsApp number',
                    'session_id' => $otpData['session_id'],
                    'reservation_id' => $reservation->id,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Please try again in a moment',
                ], 429);
            }
        } finally {
            optional($lock)->release();
        }
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'pax' => 'nullable|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Check if date is open
        if (!ReservationSetting::isDateOpen($data['date'])) {
            return response()->json([
                'available' => false,
                'message' => 'Reservations are closed for this date',
            ]);
        }

        // Get available tables for the date and time
        $cacheKey = "available_tables_{$data['date']}_{$data['time']}";
        if (isset($data['pax'])) {
            $cacheKey .= "_{$data['pax']}";
        }

        $availableTables = Cache::remember(
            $cacheKey,
            300,
            function () use ($data) {
                $requestedDateTime = \Carbon\Carbon::parse($data['date'] . ' ' . $data['time']);
                
                // Get all tables that are available
                $allTables = Table::where('is_available', true)
                    ->where('capacity', '>=', $data['pax'] ?? 1)
                    ->with(['reservations' => function ($q) use ($data) {
                        $q->where('reservation_date', $data['date'])
                            ->where('status', '!=', 'cancelled');
                    }])
                    ->get();

                // Filter out tables that have conflicting reservations
                $availableTables = $allTables->filter(function ($table) use ($requestedDateTime) {
                    foreach ($table->reservations as $reservation) {
                        $reservationDateTime = \Carbon\Carbon::parse(
                            $reservation->reservation_date->format('Y-m-d') . ' ' . $reservation->reservation_time
                        );
                        $reservationEndTime = $reservationDateTime->copy()->addHour();
                        
                        // Check if requested time falls within the reservation's 1-hour block
                        // A reservation at 9:00am blocks from 9:00am (inclusive) to 10:00am (exclusive)
                        if ($requestedDateTime->gte($reservationDateTime) && $requestedDateTime->lt($reservationEndTime)) {
                            return false; // Table is not available
                        }
                    }
                    return true; // Table is available
                });

                // Filter by capacity if pax is provided
                if (isset($data['pax']) && $data['pax']) {
                    $availableTables = $availableTables->filter(function ($table) use ($data) {
                        return $table->capacity >= $data['pax'];
                    });
                }

                return $availableTables->map(function ($table) {
                    return [
                        'id' => $table->id,
                        'name' => $table->name,
                        'capacity' => $table->capacity,
                    ];
                })->sortBy('capacity')->values();
            }
        );

        return response()->json([
            'available' => true,
            'tables' => $availableTables,
            'count' => $availableTables->count(),
        ]);
    }

    /**
     * Get closed dates (all future closed dates, no limit)
     */
    public function getClosedDates(): JsonResponse
    {
        $startDate = now()->format('Y-m-d');

        $closedDates = Cache::remember(
            "closed_dates_all",
            3600,
            function () use ($startDate) {
                return ReservationSetting::where('is_open', false)
                    ->where('date', '>=', $startDate) // Only future dates
                    ->orderBy('date')
                    ->pluck('date')
                    ->map(fn($date) => $date->format('Y-m-d'))
                    ->toArray();
            }
        );

        return response()->json([
            'success' => true,
            'closed_dates' => $closedDates,
        ]);
    }

    /**
     * Verify OTP and confirm reservation
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->otpService->verifyOtp(
            $request->session_id,
            $request->otp_code
        );

        if ($result['success']) {
            // Update reservation status to confirmed
            $reservation = Reservation::find($result['reservation_id']);
            if ($reservation) {
                $reservation->update([
                    'status' => 'confirmed',
                    'otp_verified' => true,
                ]);

                // Mark table as unavailable
                $reservation->table->update(['is_available' => false]);

                // Dispatch job to send notifications
                ProcessReservation::dispatch(
                    array_merge($reservation->toArray(), ['id' => $reservation->id]),
                    $reservation->ip_address,
                    $reservation->user_agent
                )->afterResponse();

                return response()->json([
                    'success' => true,
                    'message' => 'Reservation confirmed successfully',
                    'status' => 'confirmed',
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'OTP verification failed',
        ], 400);
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
            ], 422);
        }

        $otp = $this->otpService->getOtpBySession($request->session_id);
        
        if (!$otp || $otp->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session',
            ], 400);
        }

        // Generate new OTP
        $otpData = $this->otpService->generateOtp($otp->phone_number, $otp->reservation_id);
        
        // Get customer name from reservation if available
        $customerName = null;
        if ($otp->reservation_id) {
            $reservation = Reservation::find($otp->reservation_id);
            $customerName = $reservation->customer_name ?? null;
        }
        
        // Send OTP via WhatsApp
        $this->whatsAppService->sendOtp($otp->phone_number, $otpData['otp_code'], $customerName);

        // Update reservation with new session ID
        if ($otp->reservation_id) {
            Reservation::where('id', $otp->reservation_id)
                ->update(['otp_session_id' => $otpData['session_id']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP resent successfully',
            'session_id' => $otpData['session_id'],
        ]);
    }

    /**
     * Check reservation status
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $sessionId = $request->query('session_id');
        
        if (!$sessionId) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Session ID required',
            ], 400);
        }

        $otp = $this->otpService->getOtpBySession($sessionId);
        
        if (!$otp || !$otp->reservation_id) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Reservation not found',
            ]);
        }

        $reservation = Reservation::with('table')->find($otp->reservation_id);

        if (!$reservation) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Reservation not found',
            ]);
        }

        return response()->json([
            'status' => $reservation->status,
            'message' => $reservation->status === 'confirmed' 
                ? 'Reservation confirmed successfully' 
                : 'Reservation is being processed',
            'reservation_id' => $reservation->id,
        ]);
    }
}

