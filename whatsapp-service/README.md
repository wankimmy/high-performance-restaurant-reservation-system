# WhatsApp Baileys Service

This service provides WhatsApp Web integration using [Baileys](https://github.com/WhiskeySockets/Baileys) for the Laravel HPBS application.

## Features

- WhatsApp Web connection via QR code scanning
- Send OTP messages to customers
- Send reservation confirmations
- Send arrival verification OTPs
- Persistent authentication state
- RESTful API for Laravel integration

## Setup

1. The service runs automatically when Docker Compose starts
2. Access the admin panel at `/admin/whatsapp-settings`
3. Click "Connect WhatsApp" to generate a QR code
4. Scan the QR code with your WhatsApp mobile app
5. Once connected, the service will automatically send messages

## API Endpoints

- `GET /api/status` - Get connection status
- `GET /api/qr` - Get QR code for authentication
- `POST /api/connect` - Initiate WhatsApp connection
- `POST /api/disconnect` - Disconnect WhatsApp
- `POST /api/send-message` - Send a message
  ```json
  {
    "phone": "+60123456789",
    "message": "Your message here"
  }
  ```

## Authentication State

The authentication state is stored in `/app/auth_state` directory and persisted via Docker volume. Once authenticated, the service will automatically reconnect on restart.

## Phone Number Format

Phone numbers should be in international format with country code:
- Example: `+60123456789` (Malaysia)
- Example: `+1234567890` (USA)

## Troubleshooting

1. **QR Code not showing**: Check if the service is running and accessible
2. **Connection fails**: Ensure WhatsApp is not already connected on another device
3. **Messages not sending**: Verify the phone number format and connection status
