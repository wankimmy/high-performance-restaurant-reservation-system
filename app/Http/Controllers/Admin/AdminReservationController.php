<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\ReservationSetting;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class AdminReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with('table')
            ->orderBy('reservation_date', 'desc')
            ->orderBy('reservation_time', 'desc');

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

        // Clear cache for availability
        Cache::forget("available_tables_{$reservation->reservation_date}_{$reservation->reservation_time}");

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully',
        ]);
    }

    public function toggleDateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'is_open' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $setting = ReservationSetting::firstOrNew(['date' => $data['date']]);
        $setting->is_open = $data['is_open'];
        $setting->save();

        // Clear cache
        ReservationSetting::clearCache($data['date']);

        return response()->json([
            'success' => true,
            'message' => 'Reservation date status updated',
            'setting' => $setting,
        ]);
    }

    public function getSettings(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        
        $settings = ReservationSetting::where('date', '>=', now()->format('Y-m-d'))
            ->orderBy('date')
            ->get();

        if ($request->expectsJson()) {
            return response()->json($settings);
        }

        return view('admin.settings.index', compact('settings'));
    }
}

