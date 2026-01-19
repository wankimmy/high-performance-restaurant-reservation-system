<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ConfirmReservation;
use App\Jobs\CreateReservation;
use App\Models\Reservation;
use App\Models\ReservationSetting;
use App\Models\RestaurantSetting;
use App\Models\Table;
use App\Services\OtpService;
use App\Services\WhatsAppService;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Check if table already has a reservation at this time
        // Availability is determined by checking reservations, not the is_available field
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

        // Use Redis lock to prevent race conditions (lightweight check only)
        $lockKey = "reservation_lock_{$data['table_id']}_{$data['reservation_date']}_{$data['reservation_time']}";
        $lock = Cache::lock($lockKey, 5);

        try {
            if ($lock->get()) {
                // Quick availability check (no DB write)
                if ($table->hasReservationAt($data['reservation_date'], $data['reservation_time'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This table is already reserved at the selected time',
                    ], 409);
                }

                // Generate session ID for tracking
                $sessionId = Str::uuid()->toString();

                // Store reservation data temporarily in cache (10 minutes)
                Cache::put("reservation_data_{$sessionId}", [
                    'table_id' => $data['table_id'],
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'],
                    'customer_phone' => $data['customer_phone'],
                    'pax' => $data['pax'],
                    'reservation_date' => $data['reservation_date'],
                    'reservation_time' => $data['reservation_time'],
                    'notes' => $data['notes'] ?? null,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ], 600);

                // Initialize status in cache
                Cache::put("reservation_status_{$sessionId}", [
                    'status' => 'processing',
                    'message' => 'Creating reservation...',
                ], 600);

                // Queue reservation creation (async - no DB write in request)
                CreateReservation::dispatch(
                    $data,
                    $sessionId,
                    $request->ip(),
                    $request->userAgent()
                )->afterResponse();

                return response()->json([
                    'success' => true,
                    'message' => 'Reservation request received. Processing...',
                    'session_id' => $sessionId,
                ], 202); // 202 Accepted - request is being processed
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
                
                // Get all tables that meet capacity requirements
                // Availability is determined by checking reservations, not the is_available field
                $allTables = Table::where('capacity', '>=', $data['pax'] ?? 1)
                    ->with(['reservations' => function ($q) use ($data) {
                        $q->where('reservation_date', $data['date'])
                            ->where('status', '!=', 'cancelled');
                    }])
                    ->get();

                // Filter out tables that have conflicting reservations
                $availableTables = $allTables->filter(function ($table) use ($requestedDateTime) {
                    $requestedEndTime = $requestedDateTime->copy()->addMinutes(105); // Requested slot also lasts 1 hour 45 minutes
                    
                    foreach ($table->reservations as $reservation) {
                        $reservationDateTime = \Carbon\Carbon::parse(
                            $reservation->reservation_date->format('Y-m-d') . ' ' . $reservation->reservation_time
                        );
                        $reservationEndTime = $reservationDateTime->copy()->addMinutes(105); // 1 hour 45 minutes
                        
                        // Check if the requested time slot overlaps with any existing reservation
                        // Overlap occurs if: requested start < reservation end AND requested end > reservation start
                        if ($requestedDateTime->lt($reservationEndTime) && $requestedEndTime->gt($reservationDateTime)) {
                            return false; // Table is not available due to overlap
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

        // Ensure tables is always an array, even when empty
        $tablesArray = $availableTables->toArray();
        
        return response()->json([
            'available' => count($tablesArray) > 0,
            'tables' => $tablesArray,
            'count' => count($tablesArray),
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
            // Get reservation ID from OTP
            $otp = $this->otpService->getOtpBySession($request->session_id);
            
            if ($otp && $otp->reservation_id) {
                // Update status in cache immediately
                Cache::put("reservation_status_{$request->session_id}", [
                    'status' => 'confirming',
                    'reservation_id' => $otp->reservation_id,
                    'message' => 'Confirming reservation...',
                ], 600);

                // Queue confirmation (async - no DB write in request)
                ConfirmReservation::dispatch(
                    $otp->reservation_id,
                    $request->session_id
                )->afterResponse();

                return response()->json([
                    'success' => true,
                    'message' => 'OTP verified. Confirming reservation...',
                    'status' => 'processing',
                ], 202); // 202 Accepted - request is being processed
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
        
        // Get the new OTP from database
        $newOtp = $this->otpService->getOtpBySession($otpData['session_id']);
        
        // Get customer name from reservation if available
        $customerName = null;
        if ($otp->reservation_id) {
            $reservation = Reservation::find($otp->reservation_id);
            $customerName = $reservation->customer_name ?? null;
        }
        
        // Send OTP via WhatsApp (skip for k6 load tests)
        $isK6Test = $request->header('X-k6-Test') === 'true' || 
                    str_contains($request->userAgent() ?? '', 'k6-load-test') ||
                    str_contains($request->userAgent() ?? '', 'k6-stress-test') ||
                    str_contains($request->userAgent() ?? '', 'k6-booking-flow') ||
                    str_contains($request->userAgent() ?? '', 'k6');
        
        if ($newOtp && !$isK6Test) {
            $this->whatsAppService->sendOtp($otp->phone_number, $newOtp->otp_code, $customerName);
        }

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
     * Get restaurant settings (API) - optionally for a specific date
     */
    public function getRestaurantSettings(Request $request): JsonResponse
    {
        $date = $request->query('date');
        
        // Use per-date settings if date is provided, otherwise use global settings
        if ($date) {
            $dateSettings = ReservationSetting::getDateSettings($date);
            return response()->json([
                'success' => true,
                'settings' => [
                    'opening_time' => $dateSettings['opening_time'],
                    'closing_time' => $dateSettings['closing_time'],
                    'deposit_per_pax' => (float) $dateSettings['deposit_per_pax'],
                    'time_slot_interval' => (int) $dateSettings['time_slot_interval'],
                ],
            ]);
        }
        
        $settings = RestaurantSetting::getSettings();
        
        return response()->json([
            'success' => true,
            'settings' => [
                'opening_time' => $settings->opening_time,
                'closing_time' => $settings->closing_time,
                'deposit_per_pax' => (float) $settings->deposit_per_pax,
                'time_slot_interval' => (int) ($settings->time_slot_interval ?? 30),
            ],
        ]);
    }

    /**
     * Get available time slots (optionally for a specific date)
     */
    public function getTimeSlots(Request $request): JsonResponse
    {
        $date = $request->query('date');
        
        // Use per-date settings if date is provided, otherwise use global settings
        if ($date) {
            $dateSettings = ReservationSetting::getDateSettings($date);
            $openingTime = \Carbon\Carbon::parse($dateSettings['opening_time']);
            $closingTime = \Carbon\Carbon::parse($dateSettings['closing_time']);
            $interval = $dateSettings['time_slot_interval'];
        } else {
            $settings = RestaurantSetting::getSettings();
            $openingTime = \Carbon\Carbon::parse($settings->opening_time);
            $closingTime = \Carbon\Carbon::parse($settings->closing_time);
            $interval = $settings->time_slot_interval ?? 30;
        }
        
        $slots = [];
        $currentTime = $openingTime->copy();
        
        while ($currentTime->lt($closingTime)) {
            $endTime = $currentTime->copy()->addMinutes($interval);
            
            if ($endTime->gt($closingTime)) {
                break;
            }
            
            $slots[] = [
                'start_time' => $currentTime->format('H:i'),
                'end_time' => $endTime->format('H:i'),
                'display' => $currentTime->format('g:i A'),
                'value' => $currentTime->format('H:i'),
            ];
            
            $currentTime->addMinutes($interval);
        }
        
        return response()->json([
            'success' => true,
            'time_slots' => $slots,
        ]);
    }

    /**
     * Check reservation status (from cache - no DB query)
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

        // Get status from cache (fast - no DB query)
        $status = Cache::get("reservation_status_{$sessionId}");

        if (!$status) {
            // Fallback: try to get from OTP if cache expired
            $otp = $this->otpService->getOtpBySession($sessionId);
            
            if ($otp && $otp->reservation_id) {
                $reservation = Reservation::with('table')->find($otp->reservation_id);
                
                if ($reservation) {
                    $status = [
                        'status' => $reservation->status,
                        'reservation_id' => $reservation->id,
                        'message' => $reservation->status === 'confirmed' 
                            ? 'Reservation confirmed successfully' 
                            : 'Reservation is being processed',
                    ];
                    // Re-cache the status
                    Cache::put("reservation_status_{$sessionId}", $status, 600);
                }
            }
        }

        if (!$status) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Reservation not found',
            ]);
        }

        return response()->json($status);
    }
}

