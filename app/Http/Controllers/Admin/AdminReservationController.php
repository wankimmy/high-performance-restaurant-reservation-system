<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Table;
use App\Services\OtpService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AdminReservationController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private WhatsAppService $whatsAppService
    ) {}

    public function index(Request $request)
    {
        $query = Reservation::with('table')
            ->orderBy('reservations.id', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->where('reservation_date', $request->date);
        }

        $reservations = $query->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($reservations);
        }

        return view('admin.reservations.index', compact('reservations'));
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $reservation = Reservation::findOrFail($id);
        
        if ($reservation->isCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Reservation is already cancelled',
            ], 400);
        }

        $reservation->cancel();

        // Make table available again when reservation is cancelled
        $table = $reservation->table;
        if ($table) {
            // Check if there are no other active reservations for this table
            $hasActiveReservations = Reservation::where('table_id', $table->id)
                ->where('status', '!=', 'cancelled')
                ->where('id', '!=', $reservation->id)
                ->exists();
            
            if (!$hasActiveReservations) {
                $table->is_available = true;
                $table->save();
            }
        }

        // Clear cache for availability
        Cache::forget("available_tables_{$reservation->reservation_date}_{$reservation->reservation_time}");

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully',
        ]);
    }

    /**
     * Request arrival verification - sends OTP to customer
     */
    public function requestArrivalVerification(Request $request, int $id): JsonResponse
    {
        $reservation = Reservation::with('table')->findOrFail($id);

        if ($reservation->has_arrived) {
            return response()->json([
                'success' => false,
                'message' => 'Customer has already been marked as arrived',
            ], 400);
        }

        if ($reservation->status !== 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Only confirmed reservations can be verified for arrival',
            ], 400);
        }

        // Generate arrival OTP
        $otpData = $this->otpService->generateOtp($reservation->customer_phone, $reservation->id);

        // Get OTP code from database
        $otp = $this->otpService->getOtpBySession($otpData['session_id']);

        // Send arrival OTP via WhatsApp
        if ($otp) {
            $this->whatsAppService->sendArrivalOtp(
                $reservation->customer_phone,
                $otp->otp_code,
                [
                    'id' => $reservation->id,
                    'customer_name' => $reservation->customer_name,
                    'table_name' => $reservation->table->name,
                    'reservation_date' => $reservation->reservation_date->format('M d, Y'),
                    'reservation_time' => \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A'),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to customer WhatsApp. Please ask customer to show the OTP code.',
            'session_id' => $otpData['session_id'],
        ]);
    }

    /**
     * Verify arrival OTP entered by admin
     */
    public function verifyArrivalOtp(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $reservation = Reservation::with('table')->findOrFail($id);

        if ($reservation->has_arrived) {
            return response()->json([
                'success' => false,
                'message' => 'Customer has already been marked as arrived',
            ], 400);
        }

        // Find the latest OTP for this reservation
        $otp = \App\Models\Otp::where('reservation_id', $reservation->id)
            ->where('is_verified', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'No pending OTP found. Please request verification first.',
            ], 400);
        }

        // Verify OTP
        if ($otp->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 400);
        }

        if ($otp->otp_code !== $request->otp_code) {
            $otp->increment('attempts');
            
            if ($otp->attempts >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed attempts. Please request a new OTP.',
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP code. Please try again.',
            ], 400);
        }

        // Mark OTP as verified and mark reservation as arrived
        $otp->update(['is_verified' => true]);
        $reservation->update([
            'has_arrived' => true,
            'arrived_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer arrival verified successfully.',
        ]);
    }
}

