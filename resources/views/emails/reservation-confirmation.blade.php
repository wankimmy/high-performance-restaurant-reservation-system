<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0;">Reservation Confirmed!</h1>
    </div>
    
    <div style="background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px;">
        <p style="font-size: 16px; margin-bottom: 20px;">Dear {{ $reservation->customer_name }},</p>
        
        <p style="font-size: 16px; margin-bottom: 20px;">Thank you for your reservation! We're excited to have you dine with us.</p>
        
        <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
            <h2 style="color: #667eea; margin-top: 0;">Reservation Details</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #666; width: 40%;">Reservation ID:</td>
                    <td style="padding: 8px 0; font-weight: bold;">#{{ $reservation->id }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Table:</td>
                    <td style="padding: 8px 0; font-weight: bold;">{{ $reservation->table->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Date:</td>
                    <td style="padding: 8px 0; font-weight: bold;">{{ $reservation->reservation_date->format('F d, Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Time:</td>
                    <td style="padding: 8px 0; font-weight: bold;">{{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Guests:</td>
                    <td style="padding: 8px 0; font-weight: bold;">{{ $reservation->pax }} {{ $reservation->pax == 1 ? 'person' : 'people' }}</td>
                </tr>
                @if($reservation->notes)
                <tr>
                    <td style="padding: 8px 0; color: #666;">Special Requests:</td>
                    <td style="padding: 8px 0; font-weight: bold;">{{ $reservation->notes }}</td>
                </tr>
                @endif
            </table>
        </div>
        
        <p style="font-size: 14px; color: #666; margin-bottom: 20px;">
            We look forward to serving you! If you need to make any changes to your reservation, please contact us as soon as possible.
        </p>
        
        <p style="font-size: 14px; color: #666; margin: 0;">
            Best regards,<br>
            <strong>Restaurant Team</strong>
        </p>
    </div>
</body>
</html>

