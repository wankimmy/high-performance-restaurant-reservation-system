<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(): View
    {
        return view('booking.index');
    }

    public function verifyOtp(): View
    {
        return view('booking.verify-otp');
    }

    public function queue(): View
    {
        return view('booking.queue');
    }

    public function result(Request $request): View
    {
        $sessionId = $request->query('session_id');
        $status = $request->query('status', 'failed');
        $message = $request->query('message', '');
        
        $reservation = null;
        if ($sessionId && $status === 'confirmed') {
            $otp = Otp::where('session_id', $sessionId)->first();
            if ($otp && $otp->reservation_id) {
                $reservation = Reservation::with('table')->find($otp->reservation_id);
            }
        }
        
        return view('booking.result', [
            'status' => $status,
            'message' => $message,
            'reservation' => $reservation,
        ]);
    }
}
