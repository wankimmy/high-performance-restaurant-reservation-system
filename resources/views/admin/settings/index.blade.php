@extends('layouts.admin')

@section('title', 'Admin - Settings')

@section('page-title', '⚙️ Reservation Settings')

@section('content')
<div class="container">
    <h2 style="margin-bottom: 20px;">Control Reservation Dates</h2>
    
    <form id="settingsForm">
        <div class="form-group">
            <label for="date">Date</label>
            <input type="date" id="date" name="date" required min="{{ date('Y-m-d') }}">
        </div>
        
        <div class="form-group">
            <label>
                <span style="margin-right: 10px;">Open for Reservations</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="is_open" name="is_open" checked>
                    <span class="slider"></span>
                </label>
            </label>
        </div>
        
        <button type="submit" class="btn" style="background: #667eea; color: white; padding: 10px 20px;">Save Setting</button>
    </form>

    <div class="settings-list">
        <h3 style="margin-bottom: 15px;">Current Settings</h3>
        @forelse($settings as $setting)
        <div class="setting-item {{ $setting->is_open ? 'open' : 'closed' }}">
            <div>
                <strong>{{ $setting->date->format('M d, Y') }}</strong>
                <span style="margin-left: 10px; font-size: 14px;">
                    {{ $setting->is_open ? '✅ Open' : '❌ Closed' }}
                </span>
            </div>
            <button class="btn" style="background: #667eea; color: white;" onclick="toggleDate('{{ $setting->date->format('Y-m-d') }}', {{ $setting->is_open ? 'false' : 'true' }})">
                {{ $setting->is_open ? 'Close' : 'Open' }}
            </button>
        </div>
        @empty
        <p style="color: #999; text-align: center; padding: 20px;">No settings configured. Dates are open by default.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    document.getElementById('settingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            date: formData.get('date'),
            is_open: document.getElementById('is_open').checked
        };

        try {
            const response = await fetch('/admin/settings/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showMessage('Setting saved successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage(result.message || 'Failed to save setting', 'error');
            }
        } catch (error) {
            showMessage('Error saving setting', 'error');
        }
    });

    function toggleDate(date, isOpen) {
        fetch('/admin/settings/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ date, is_open: isOpen })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Setting updated successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage(data.message || 'Failed to update setting', 'error');
            }
        })
        .catch(error => {
            showMessage('Error updating setting', 'error');
        });
    }

    function showMessage(text, type) {
        const messageDiv = document.getElementById('message');
        messageDiv.textContent = text;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 3000);
    }
</script>
@endpush
