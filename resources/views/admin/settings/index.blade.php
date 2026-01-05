@extends('layouts.admin')

@section('title', 'Settings')
@section('page-title', 'Reservation Settings')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Add/Update Setting Form -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add/Update Date Setting</h3>
            <form id="settingsForm">
                <div class="mb-4">
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                    <input type="date" id="date" name="date" required min="{{ date('Y-m-d') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="is_open" name="is_open" checked
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">Open for Reservations</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">Toggle to open or close this date for reservations</p>
                </div>
                
                <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Save Setting
                </button>
            </form>
        </div>
    </div>

    <!-- Current Settings List -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Current Settings</h3>
            <div class="space-y-3">
                @forelse($settings as $setting)
                <div class="flex items-center justify-between p-4 rounded-lg border {{ $setting->is_open ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                    <div>
                        <div class="font-medium text-gray-900">{{ $setting->date->format('l, M d, Y') }}</div>
                        <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $setting->is_open ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $setting->is_open ? 'Open' : 'Closed' }}
                        </span>
                    </div>
                    <button onclick="toggleDate('{{ $setting->date->format('Y-m-d') }}', {{ $setting->is_open ? 'false' : 'true' }})"
                            class="px-3 py-1 text-sm rounded-md {{ $setting->is_open ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white">
                        {{ $setting->is_open ? 'Close' : 'Open' }}
                    </button>
                </div>
                @empty
                <div class="text-center py-8 text-gray-500">
                    <p>No settings configured.</p>
                    <p class="text-sm mt-1">Dates are open by default.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div id="message" class="hidden fixed top-4 right-4 z-50"></div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    document.getElementById('settingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const data = {
            date: document.getElementById('date').value,
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
        .catch(error => showMessage('Error updating setting', 'error'));
    }

    function showMessage(text, type) {
        const messageDiv = document.getElementById('message');
        const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        messageDiv.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg`;
        messageDiv.textContent = text;
        messageDiv.classList.remove('hidden');
        setTimeout(() => messageDiv.classList.add('hidden'), 3000);
    }
</script>
@endpush
