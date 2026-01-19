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
    private const RESERVATION_CACHE_TTL = 120; // 2 minutes
    private const RESERVATION_HOLD_TTL = 120; // 2 minutes
    private const RESERVATION_MAX_DAYS_AHEAD = 30;

    public function __construct(
        private OtpService $otpService,
        private WhatsAppService $whatsAppService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $maxReservationDate = now()->addDays(self::RESERVATION_MAX_DAYS_AHEAD)->format('Y-m-d');
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:tables,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'pax' => 'required|integer|min:1|max:20',
            'reservation_date' => "required|date|after_or_equal:today|before_or_equal:{$maxReservationDate}",
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

        // Create a temporary hold to prevent race conditions across async jobs
        $sessionId = Str::uuid()->toString();
        $holdKey = "reservation_hold_{$data['table_id']}_{$data['reservation_date']}_{$data['reservation_time']}";
        $holdAcquired = Cache::add($holdKey, $sessionId, self::RESERVATION_HOLD_TTL);

        if (!$holdAcquired) {
            return response()->json([
                'success' => false,
                'message' => 'This table is already being booked at the selected time. Please choose a different table or time.',
            ], 409);
        }

        // Re-check after acquiring hold to avoid last-millisecond conflicts
        if ($table->hasReservationAt($data['reservation_date'], $data['reservation_time'])) {
            Cache::forget($holdKey);
            return response()->json([
                'success' => false,
                'message' => 'This table is already reserved at the selected time. Please choose a different table or time.',
            ], 409);
        }

        try {
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
            ], self::RESERVATION_CACHE_TTL);

            // Initialize status in cache
            Cache::put("reservation_status_{$sessionId}", [
                'status' => 'processing',
                'message' => 'Creating reservation...',
            ], self::RESERVATION_CACHE_TTL);

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
        } catch (\Throwable $e) {
            Cache::forget($holdKey);
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create reservation request. Please try again.',
            ], 500);
        }
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        $maxReservationDate = now()->addDays(self::RESERVATION_MAX_DAYS_AHEAD)->format('Y-m-d');
        $validator = Validator::make($request->all(), [
            'date' => "required|date|after_or_equal:today|before_or_equal:{$maxReservationDate}",
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

        $requestedDateTime = \Carbon\Carbon::parse($data['date'] . ' ' . $data['time']);
        $requestedEndTime = $requestedDateTime->copy()->addMinutes(105);
        $pax = $data['pax'] ?? 1;

        $availableTables = Cache::remember(
            $cacheKey,
            300,
            function () use ($data, $requestedDateTime, $requestedEndTime, $pax) {
                $requestedStart = $requestedDateTime->format('Y-m-d H:i:s');
                $requestedEnd = $requestedEndTime->format('Y-m-d H:i:s');

                // Availability is determined by checking reservations, not the is_available field
                // Use a NOT EXISTS filter to avoid loading reservations into memory
                $tables = Table::where('capacity', '>=', $pax)
                    ->whereDoesntHave('reservations', function ($q) use ($data, $requestedStart, $requestedEnd) {
                        $q->whereIn('status', ['pending', 'confirmed'])
                            ->where('reservation_start_at', '<', $requestedEnd)
                            ->where('reservation_end_at', '>', $requestedStart);
                    })
                    ->orderBy('capacity')
                    ->get(['id', 'name', 'capacity']);

                return $tables->map(function ($table) {
                    return [
                        'id' => $table->id,
                        'name' => $table->name,
                        'capacity' => $table->capacity,
                    ];
                })->values();
            }
        );

        // Ensure tables is always an array, even when empty
        $tablesArray = $availableTables->toArray();
        
        return response()->json([
            'available' => count($tablesArray) > 0,
            'tables' => $tablesArray,
            'count' => count($tablesArray),
        ])->header('Cache-Control', 'public, max-age=60'); // 1 minute HTTP cache (availability changes frequently)
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
        ])->header('Cache-Control', 'public, max-age=300'); // 5 minutes HTTP cache
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
                ], self::RESERVATION_CACHE_TTL);

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
        $maxReservationDate = now()->addDays(self::RESERVATION_MAX_DAYS_AHEAD)->format('Y-m-d');

        if ($date) {
            $validator = Validator::make(['date' => $date], [
                'date' => "date|after_or_equal:today|before_or_equal:{$maxReservationDate}",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date must be within the next 30 days',
                    'errors' => $validator->errors(),
                ], 422);
            }
        }
        
        // Cache key based on date or global
        $cacheKey = $date ? "restaurant_settings_date_{$date}" : "restaurant_settings_global";
        
        // Use per-date settings if date is provided, otherwise use global settings
        $settingsData = Cache::remember($cacheKey, 600, function () use ($date) {
            if ($date) {
                $dateSettings = ReservationSetting::getDateSettings($date);
                return [
                    'opening_time' => $dateSettings['opening_time'],
                    'closing_time' => $dateSettings['closing_time'],
                    'deposit_per_pax' => (float) $dateSettings['deposit_per_pax'],
                    'time_slot_interval' => (int) $dateSettings['time_slot_interval'],
                ];
            }
            
            $settings = RestaurantSetting::getSettings();
            return [
                'opening_time' => $settings->opening_time,
                'closing_time' => $settings->closing_time,
                'deposit_per_pax' => (float) $settings->deposit_per_pax,
                'time_slot_interval' => (int) ($settings->time_slot_interval ?? 30),
            ];
        });
        
        return response()->json([
            'success' => true,
            'settings' => $settingsData,
        ])->header('Cache-Control', 'public, max-age=300'); // 5 minutes HTTP cache
    }

    /**
     * Get available time slots (optionally for a specific date)
     */
    public function getTimeSlots(Request $request): JsonResponse
    {
        $date = $request->query('date');
        $maxReservationDate = now()->addDays(self::RESERVATION_MAX_DAYS_AHEAD)->format('Y-m-d');

        if ($date) {
            $validator = Validator::make(['date' => $date], [
                'date' => "date|after_or_equal:today|before_or_equal:{$maxReservationDate}",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date must be within the next 30 days',
                    'errors' => $validator->errors(),
                ], 422);
            }
        }
        
        // Cache key based on date or global
        $cacheKey = $date ? "time_slots_date_{$date}" : "time_slots_global";
        
        $slots = Cache::remember($cacheKey, 600, function () use ($date) {
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
            
            return $slots;
        });
        
        return response()->json([
            'success' => true,
            'time_slots' => $slots,
        ])->header('Cache-Control', 'public, max-age=300'); // 5 minutes HTTP cache
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
                    Cache::put("reservation_status_{$sessionId}", $status, self::RESERVATION_CACHE_TTL);
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

