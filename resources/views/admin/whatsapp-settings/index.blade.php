@extends('layouts.admin')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">WhatsApp Settings</h1>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">WhatsApp Configuration</h5>
                </div>
                <div class="card-body">
                    <!-- Connection Status -->
                    <div class="mb-4">
                        <h6>Connection Status</h6>
                        <div class="d-flex align-items-center gap-3">
                            <div id="statusIndicator" class="badge bg-secondary">
                                <span id="statusText">Checking...</span>
                            </div>
                            <button id="refreshStatusBtn" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>

                    <!-- QR Code Display -->
                    <div id="qrCodeContainer" class="mb-4" style="display: none;">
                        <h6>Scan QR Code</h6>
                        <p class="text-muted small">Scan this QR code with your WhatsApp to connect:</p>
                        <div class="d-flex justify-content-center">
                            <div id="qrCodeDisplay" class="border p-3 bg-white">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small mt-2">
                            <strong>Instructions:</strong> Open WhatsApp on your phone → Settings → Linked Devices → Link a Device → Scan QR Code
                        </p>
                    </div>

                    <!-- Connection Actions -->
                    <div class="mb-4">
                        <button id="connectBtn" class="btn btn-primary me-2">
                            <i class="bi bi-link-45deg"></i> Connect WhatsApp
                        </button>
                        <button id="disconnectBtn" class="btn btn-danger" style="display: none;">
                            <i class="bi bi-x-circle"></i> Disconnect
                        </button>
                    </div>

                    <!-- Settings Form -->
                    <form id="settingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="is_enabled" class="form-label">Enable WhatsApp</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" {{ $settings->is_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_enabled">
                                            Enable WhatsApp messaging
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_url" class="form-label">Service URL</label>
                                    <input type="url" class="form-control" id="service_url" name="service_url" 
                                           value="{{ $settings->service_url }}" 
                                           placeholder="http://whatsapp-service:3001">
                                    <small class="form-text text-muted">URL of the WhatsApp Baileys service</small>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
(function() {
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
        statusIndicator.className = 'badge';
        if (connected) {
            statusIndicator.classList.add('bg-success');
        } else if (status === 'qr_ready') {
            statusIndicator.classList.add('bg-warning');
        } else if (status === 'error') {
            statusIndicator.classList.add('bg-danger');
        } else {
            statusIndicator.classList.add('bg-secondary');
        }

        // Show/hide buttons
        if (connected) {
            connectBtn.style.display = 'none';
            disconnectBtn.style.display = 'inline-block';
            qrCodeContainer.style.display = 'none';
        } else {
            connectBtn.style.display = 'inline-block';
            disconnectBtn.style.display = 'none';
            
            if (hasQr) {
                qrCodeContainer.style.display = 'block';
            } else {
                qrCodeContainer.style.display = 'none';
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
                
                // If QR code is available, fetch and display it
                if (data.hasQr) {
                    fetchQrCode();
                }
            }
        } catch (error) {
            console.error('Error checking status:', error);
        }
    }

    // Fetch QR code
    async function fetchQrCode() {
        try {
            const response = await fetch('/admin/whatsapp-settings/qr');
            const data = await response.json();
            
            if (data.success && data.qr) {
                // Clear previous QR code
                qrCodeDisplay.innerHTML = '';
                
                // Generate QR code image
                QRCode.toCanvas(qrCodeDisplay, data.qr, {
                    width: 256,
                    margin: 2,
                }, function (error) {
                    if (error) {
                        console.error('Error generating QR code:', error);
                        qrCodeDisplay.innerHTML = '<p class="text-danger">Failed to generate QR code</p>';
                    }
                });
            }
        } catch (error) {
            console.error('Error fetching QR code:', error);
        }
    }

    // Connect WhatsApp
    connectBtn.addEventListener('click', async function() {
        connectBtn.disabled = true;
        connectBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Connecting...';
        
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
                alert('Failed to connect: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error connecting:', error);
            alert('Failed to connect: ' + error.message);
        } finally {
            connectBtn.disabled = false;
            connectBtn.innerHTML = '<i class="bi bi-link-45deg"></i> Connect WhatsApp';
        }
    });

    // Disconnect WhatsApp
    disconnectBtn.addEventListener('click', async function() {
        if (!confirm('Are you sure you want to disconnect WhatsApp?')) {
            return;
        }
        
        disconnectBtn.disabled = true;
        disconnectBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Disconnecting...';
        
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
                alert('Failed to disconnect: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error disconnecting:', error);
            alert('Failed to disconnect: ' + error.message);
        } finally {
            disconnectBtn.disabled = false;
            disconnectBtn.innerHTML = '<i class="bi bi-x-circle"></i> Disconnect';
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
                alert('Settings saved successfully!');
            } else {
                alert('Failed to save settings: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error saving settings:', error);
            alert('Failed to save settings: ' + error.message);
        }
    });

    // Initial status check
    checkStatus();
    
    // Check status every 5 seconds
    statusCheckInterval = setInterval(checkStatus, 5000);
})();
</script>
@endpush
@endsection
