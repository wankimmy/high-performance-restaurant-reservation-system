@extends('layouts.app')

@section('title', 'Verify OTP')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto">
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-8 text-center">
                <h2 class="text-3xl font-bold text-white">Verify OTP</h2>
                <p class="mt-2 text-indigo-100">Enter the code sent to your WhatsApp</p>
            </div>
            
            <div class="p-6 sm:p-8">
                <div class="mb-4">
                    <p class="text-sm text-gray-600 text-center">We've sent an OTP code to your WhatsApp number. Please enter it below:</p>
                </div>
                
                <form id="otpForm" class="space-y-4">
                    <div>
                        <label for="otp_code" class="block text-sm font-medium text-gray-700 mb-2">OTP Code</label>
                        <input type="text" id="otp_code" name="otp_code" maxlength="6" 
                               class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-center text-2xl tracking-widest" 
                               placeholder="000000" required autofocus pattern="[0-9]{6}" inputmode="numeric">
                        <p class="mt-2 text-xs text-gray-500 text-center" id="otpTimer">Code expires in <span id="otpCountdown">10:00</span></p>
                    </div>
                    
                    <div id="otpMessage" class="hidden text-sm text-center p-3 rounded-lg"></div>
                    
                    <div class="flex gap-2">
                        <button type="submit" id="verifyOtpBtn" 
                                class="flex-1 bg-indigo-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Verify OTP
                        </button>
                        <button type="button" id="resendOtpBtn" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Resend
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const API_BASE = '/api/v1';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const urlParams = new URLSearchParams(window.location.search);
    const sessionId = urlParams.get('session_id');
    const reservationId = urlParams.get('reservation_id');
    let otpCountdownInterval = null;

    if (!sessionId) {
        window.location.href = '/';
    }

    // Start countdown timer
    function startOtpCountdown(seconds) {
        const countdownEl = document.getElementById('otpCountdown');
        if (!countdownEl) return;
        
        let remaining = seconds;
        
        const updateCountdown = () => {
            const minutes = Math.floor(remaining / 60);
            const secs = remaining % 60;
            countdownEl.textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;
            
            if (remaining <= 0) {
                clearInterval(otpCountdownInterval);
                countdownEl.textContent = 'Expired';
            }
            remaining--;
        };
        
        updateCountdown();
        otpCountdownInterval = setInterval(updateCountdown, 1000);
    }

    // OTP Input - Only allow numbers
    document.getElementById('otp_code')?.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // OTP Form Submission
    document.getElementById('otpForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const otpCode = document.getElementById('otp_code').value;
        if (!otpCode || otpCode.length !== 6) {
            showOtpMessage('Please enter a valid 6-digit OTP code', 'error');
            return;
        }

        const verifyBtn = document.getElementById('verifyOtpBtn');
        const originalText = verifyBtn.innerHTML;
        setButtonLoading(verifyBtn, true, originalText);

        try {
            const response = await fetch(`${API_BASE}/verify-otp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    otp_code: otpCode
                })
            });

            const result = await response.json();

            if (result.success || response.status === 202) {
                // OTP verified, confirmation is being processed asynchronously
                // Redirect to queue page to wait for confirmation
                window.location.href = `/queue?session_id=${sessionId}&reservation_id=${reservationId || ''}`;
            } else {
                setButtonLoading(verifyBtn, false, originalText);
                showOtpMessage(result.message || 'Invalid OTP code. Please try again.', 'error');
            }
        } catch (error) {
            setButtonLoading(verifyBtn, false, originalText);
            showOtpMessage('Network error. Please try again.', 'error');
        }
    });

    function showOtpMessage(text, type) {
        const messageEl = document.getElementById('otpMessage');
        messageEl.className = `text-sm ${type === 'error' ? 'text-red-600 bg-red-50' : 'text-green-600 bg-green-50'}`;
        messageEl.textContent = text;
        messageEl.classList.remove('hidden');
        
        setTimeout(() => {
            messageEl.classList.add('hidden');
        }, 5000);
    }

    // Resend OTP
    document.getElementById('resendOtpBtn')?.addEventListener('click', async function() {
        const originalText = this.innerHTML;
        setButtonLoading(this, true, originalText);

        try {
            const response = await fetch(`${API_BASE}/resend-otp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    session_id: sessionId
                })
            });

            const result = await response.json();

            if (result.success) {
                showOtpMessage('OTP resent successfully!', 'success');
                startOtpCountdown(600);
            } else {
                showOtpMessage(result.message || 'Failed to resend OTP. Please try again.', 'error');
            }
        } catch (error) {
            showOtpMessage('Network error. Please try again.', 'error');
        } finally {
            setButtonLoading(this, false, originalText);
        }
    });

    // Start countdown on page load
    startOtpCountdown(600);
</script>
@endpush
@endsection

