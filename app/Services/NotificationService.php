<?php

namespace App\Services;

use App\Mail\ReservationConfirmation;
use App\Models\Reservation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private WhatsAppService $whatsAppService
    ) {}

    /**
     * Send reservation confirmation via email and WhatsApp
     */
    public function sendReservationConfirmation(Reservation $reservation): void
    {
        // Format reservation date
        // Handle both Carbon instance (when cast) and string (when not cast)
        if ($reservation->reservation_date instanceof \Carbon\Carbon) {
            $reservationDate = $reservation->reservation_date->format('F d, Y');
        } else {
            $reservationDate = \Carbon\Carbon::parse($reservation->reservation_date)->format('F d, Y');
        }
        
        // Format reservation time (it's a string like "09:00:00", not a Carbon instance)
        // Parse it as a time and format it
        if (is_string($reservation->reservation_time)) {
            $reservationTime = \Carbon\Carbon::createFromFormat('H:i:s', $reservation->reservation_time)->format('g:i A');
        } else {
            $reservationTime = \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A');
        }
        
        $reservationDetails = [
            'id' => $reservation->id,
            'customer_name' => $reservation->customer_name,
            'customer_email' => $reservation->customer_email,
            'customer_phone' => $reservation->customer_phone,
            'table_name' => $reservation->table->name,
            'reservation_date' => $reservationDate,
            'reservation_time' => $reservationTime,
            'pax' => $reservation->pax,
            'notes' => $reservation->notes,
        ];

        // Send email
        try {
            Mail::to($reservation->customer_email, $reservation->customer_name)
                ->send(new ReservationConfirmation($reservation));
        } catch (\Exception $e) {
            Log::error('Failed to send reservation confirmation email', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Send WhatsApp
        try {
            $this->whatsAppService->sendReservationConfirmation(
                $reservation->customer_phone,
                $reservationDetails
            );
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp confirmation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

