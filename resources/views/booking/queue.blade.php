@extends('layouts.app')

@section('title', 'Processing Reservation')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-8 text-center">
                <h2 class="text-3xl font-bold text-white">Processing Your Reservation</h2>
            </div>
            
            <div class="p-8 text-center">
                <div class="mb-6">
                    <div class="inline-block animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-indigo-600"></div>
                </div>
                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-1">Queue Number</p>
                    <p class="text-3xl font-bold text-indigo-600" id="queueNumber">-</p>
                </div>
                <p class="text-lg text-gray-700 mb-2" id="statusMessage">Please wait while we process your reservation...</p>
                <p class="text-sm text-gray-500" id="statusDetail">This may take a few moments</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        const sessionId = new URLSearchParams(window.location.search).get('session_id');
        const reservationId = new URLSearchParams(window.location.search).get('reservation_id');
        
        if (!sessionId) {
            window.location.href = '/';
            return;
        }

        // Display queue number (reservation ID)
        const queueNumberEl = document.getElementById('queueNumber');
        if (reservationId && queueNumberEl) {
            queueNumberEl.textContent = '#' + reservationId;
        }

        // Poll for reservation status
        let pollCount = 0;
        const maxPolls = 60; // 60 seconds max (for async processing)
        const statusMessageEl = document.getElementById('statusMessage');
        const statusDetailEl = document.getElementById('statusDetail');
        
        const pollInterval = setInterval(async function() {
            pollCount++;
            
            if (pollCount > maxPolls) {
                clearInterval(pollInterval);
                window.location.href = `/reservation/result?session_id=${sessionId}&status=failed&message=${encodeURIComponent('Processing timeout. Please contact support.')}`;
                return;
            }

            try {
                const response = await fetch(`/api/v1/reservation-status?session_id=${sessionId}`);
                const data = await response.json();

                // Update queue number if we get it from API
                if (data.reservation_id && queueNumberEl) {
                    queueNumberEl.textContent = '#' + data.reservation_id;
                }

                // Update status message based on current status
                if (data.status === 'processing') {
                    statusMessageEl.textContent = 'Creating your reservation...';
                    statusDetailEl.textContent = 'Please wait while we process your booking';
                } else if (data.status === 'pending') {
                    // Reservation created, OTP sent - redirect to verify-otp
                    clearInterval(pollInterval);
                    const otpSessionId = data.otp_session_id || sessionId;
                    window.location.href = `/verify-otp?session_id=${otpSessionId}&reservation_id=${data.reservation_id || ''}`;
                    return;
                } else if (data.status === 'confirming') {
                    statusMessageEl.textContent = 'Confirming your reservation...';
                    statusDetailEl.textContent = 'Almost done!';
                } else if (data.status === 'confirmed') {
                    clearInterval(pollInterval);
                    window.location.href = `/reservation/result?session_id=${sessionId}&status=confirmed&message=${encodeURIComponent(data.message || 'Reservation confirmed successfully')}`;
                    return;
                } else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    window.location.href = `/reservation/result?session_id=${sessionId}&status=failed&message=${encodeURIComponent(data.message || 'Reservation failed')}`;
                    return;
                }
            } catch (error) {
                console.error('Error checking status:', error);
                // Continue polling on error
            }
        }, 1000);
    })();
</script>
@endpush
@endsection

