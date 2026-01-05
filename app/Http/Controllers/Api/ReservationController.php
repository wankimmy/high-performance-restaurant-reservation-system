<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessReservation;
use App\Models\Reservation;
use App\Models\ReservationSetting;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
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

                // Dispatch to queue for high-traffic handling
                ProcessReservation::dispatch($data, $request->ip(), $request->userAgent());

                return response()->json([
                    'success' => true,
                    'message' => 'Reservation request received and is being processed',
                ], 202);
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

        // Get available tables
        $availableTables = Cache::remember(
            "available_tables_{$data['date']}_{$data['time']}",
            300,
            function () use ($data) {
                return Table::where('is_available', true)
                    ->whereDoesntHave('reservations', function ($query) use ($data) {
                        $query->where('reservation_date', $data['date'])
                            ->where('reservation_time', $data['time'])
                            ->where('status', '!=', 'cancelled');
                    })
                    ->get(['id', 'name', 'capacity']);
            }
        );

        return response()->json([
            'available' => true,
            'tables' => $availableTables,
        ]);
    }
}

