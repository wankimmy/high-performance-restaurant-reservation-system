@extends('layouts.admin')

@section('title', 'WhatsApp Settings')
@section('page-title', 'WhatsApp Settings')

@section('content')
<div class="bg-white shadow-sm rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">WhatsApp Configuration</h3>
    </div>
    <div class="p-6">
        <!-- Connection Status -->
        <div class="mb-6">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Connection Status</h4>
            <div class="flex items-center gap-3">
                <div id="statusIndicator" class="px-3 py-1 rounded-full text-sm font-medium bg-gray-200 text-gray-800">
                    <span id="statusText">Checking...</span>
                </div>
                <button id="refreshStatusBtn" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <!-- QR Code Display -->
        <div id="qrCodeContainer" class="mb-6 hidden">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Scan QR Code</h4>
            <p class="text-sm text-gray-500 mb-4">Scan this QR code with your WhatsApp to connect:</p>
            <div class="flex justify-center">
                <div id="qrCodeDisplay" class="border border-gray-300 p-4 bg-white rounded-lg">
                    <img id="qrCodeImage" src="" alt="WhatsApp QR Code" class="hidden max-w-full h-auto" style="max-width: 256px;">
                    <div id="qrCodeLoading" class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-600 mx-auto"></div>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-3">
                <strong>Instructions:</strong> Open WhatsApp on your phone → Settings → Linked Devices → Link a Device → Scan QR Code
            </p>
        </div>

        <!-- Connection Actions -->
        <div class="mb-6">
            <button id="connectBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 mr-2">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                Connect WhatsApp
            </button>
            <button id="disconnectBtn" class="hidden inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Disconnect
            </button>
        </div>

        <!-- Settings Form -->
        <form id="settingsForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="is_enabled" class="block text-sm font-medium text-gray-700 mb-2">Enable WhatsApp</label>
                    <div class="flex items-center">
                        <input type="checkbox" id="is_enabled" name="is_enabled" {{ $settings->is_enabled ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_enabled" class="ml-2 block text-sm text-gray-900">
                            Enable WhatsApp messaging
                        </label>
                    </div>
                </div>
                <div>
                    <label for="service_url" class="block text-sm font-medium text-gray-700 mb-2">Service URL</label>
                    <input type="url" id="service_url" name="service_url" 
                           value="{{ $settings->service_url }}" 
                           placeholder="http://whatsapp-service:3001"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">URL of the WhatsApp Baileys service</p>
                </div>
            </div>

            <div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function() {
    // Initialize WhatsApp settings when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeWhatsAppSettings);
    } else {
        initializeWhatsAppSettings();
    }
    
    function initializeWhatsAppSettings() {
    const statusIndicator = document.getElementById('statusIndicator');
    const statusText = document.getElementById('statusText');
    const refreshStatusBtn = document.getElementById('refreshStatusBtn');
    const connectBtn = document.getElementById('connectBtn');
    const disconnectBtn = document.getElementById('disconnectBtn');
    const qrCodeContainer = document.getElementById('qrCodeContainer');
    const qrCodeDisplay = document.getElementById('qrCodeDisplay');
    const settingsForm = document.getElementById('settingsForm');

    let statusCheckInterval = null;

    // Update status display
    function updateStatus(status, connected, hasQr) {
        statusText.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        
        // Update badge color
        statusIndicator.className = 'px-3 py-1 rounded-full text-sm font-medium';
        if (connected) {
            statusIndicator.classList.add('bg-green-100', 'text-green-800');
        } else if (status === 'qr_ready') {
            statusIndicator.classList.add('bg-yellow-100', 'text-yellow-800');
        } else if (status === 'error') {
            statusIndicator.classList.add('bg-red-100', 'text-red-800');
        } else {
            statusIndicator.classList.add('bg-gray-200', 'text-gray-800');
        }

        // Show/hide buttons
        if (connected) {
            connectBtn.classList.add('hidden');
            disconnectBtn.classList.remove('hidden');
            qrCodeContainer.classList.add('hidden');
        } else {
            connectBtn.classList.remove('hidden');
            disconnectBtn.classList.add('hidden');
            
            if (hasQr) {
                qrCodeContainer.classList.remove('hidden');
            } else {
                qrCodeContainer.classList.add('hidden');
            }
        }
    }

    // Check status
    async function checkStatus() {
        try {
            const response = await fetch('/admin/whatsapp-settings/status');
            const data = await response.json();
            
            if (data.success) {
                updateStatus(data.status, data.connected, data.hasQr);
                
                // If QR code image is available, display it directly
                if (data.hasQr && data.qrImage) {
                    displayQrCode(data.qrImage);
                } else if (data.hasQr) {
                    // Fallback: fetch QR code if image not in status response
                    fetchQrCode();
                }
            }
        } catch (error) {
            console.error('Error checking status:', error);
        }
    }
    
    // Display QR code image
    function displayQrCode(qrImageDataUri) {
        const loadingSpinner = document.getElementById('qrCodeLoading');
        const qrCodeImage = document.getElementById('qrCodeImage');
        
        if (loadingSpinner) {
            loadingSpinner.classList.add('hidden');
        }
        
        if (qrCodeImage) {
            qrCodeImage.src = qrImageDataUri;
            qrCodeImage.classList.remove('hidden');
        }
    }

    // Fetch QR code (fallback if not in status response)
    async function fetchQrCode() {
        try {
            const response = await fetch('/admin/whatsapp-settings/qr');
            const data = await response.json();
            
            if (data.success && data.qrImage) {
                displayQrCode(data.qrImage);
            } else {
                qrCodeDisplay.innerHTML = '<p class="text-red-600 text-sm">Failed to load QR code</p>';
            }
        } catch (error) {
            console.error('Error fetching QR code:', error);
            qrCodeDisplay.innerHTML = '<p class="text-red-600 text-sm">Failed to load QR code</p>';
        }
    }

    // Connect WhatsApp
    connectBtn.addEventListener('click', async function() {
        connectBtn.disabled = true;
        connectBtn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Connecting...';
        
        try {
            const response = await fetch('/admin/whatsapp-settings/connect', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Start checking status more frequently
                if (statusCheckInterval) {
                    clearInterval(statusCheckInterval);
                }
                statusCheckInterval = setInterval(checkStatus, 2000);
                
                // Check status immediately
                setTimeout(checkStatus, 1000);
            } else {
                showToast('Failed to connect: ' + (data.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Error connecting:', error);
            showToast('Failed to connect: ' + error.message, 'error');
        } finally {
            connectBtn.disabled = false;
            connectBtn.innerHTML = '<svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg> Connect WhatsApp';
        }
    });

    // Disconnect WhatsApp
    disconnectBtn.addEventListener('click', async function() {
        if (!confirm('Are you sure you want to disconnect WhatsApp?')) {
            return;
        }
        
        disconnectBtn.disabled = true;
        disconnectBtn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Disconnecting...';
        
        try {
            const response = await fetch('/admin/whatsapp-settings/disconnect', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            
            const data = await response.json();
            
            if (data.success) {
                checkStatus();
            } else {
                showToast('Failed to disconnect: ' + (data.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Error disconnecting:', error);
            showToast('Failed to disconnect: ' + error.message, 'error');
        } finally {
            disconnectBtn.disabled = false;
            disconnectBtn.innerHTML = '<svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg> Disconnect';
        }
    });

    // Refresh status
    refreshStatusBtn.addEventListener('click', checkStatus);

    // Save settings
    settingsForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(settingsForm);
        const data = {
            is_enabled: document.getElementById('is_enabled').checked,
            service_url: formData.get('service_url'),
        };
        
        try {
            const response = await fetch('/admin/whatsapp-settings/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(data),
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('Settings saved successfully!', 'success');
            } else {
                showToast('Failed to save settings: ' + (result.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Error saving settings:', error);
            showToast('Failed to save settings: ' + error.message, 'error');
        }
    });

    // Initial status check
    checkStatus();
    
    // Check status every 5 seconds
    statusCheckInterval = setInterval(checkStatus, 5000);
    }
})();
</script>
@endpush
@endsection
