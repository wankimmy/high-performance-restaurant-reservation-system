@extends('layouts.admin')

@section('title', 'Restaurant Settings')
@section('page-title', 'Restaurant Settings')

@section('content')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- Restaurant Settings Form -->
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Restaurant Configuration</h3>
        </div>
        <div class="p-6">
            <form id="restaurantSettingsForm" class="space-y-4">
                <!-- Opening Time -->
                <div>
                    <label for="opening_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Opening Time <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="opening_time" name="opening_time" 
                           value="{{ substr($settings->opening_time, 0, 5) }}" required
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Restaurant opening time</p>
                </div>

                <!-- Closing Time -->
                <div>
                    <label for="closing_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Closing Time <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="closing_time" name="closing_time" 
                           value="{{ substr($settings->closing_time, 0, 5) }}" required
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Restaurant closing time</p>
                </div>

                <!-- Time Slot Interval -->
                <div>
                    <label for="time_slot_interval" class="block text-sm font-medium text-gray-700 mb-2">
                        Time Slot Interval (minutes) <span class="text-red-500">*</span>
                    </label>
                    <select id="time_slot_interval" name="time_slot_interval" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="15" {{ ($settings->time_slot_interval ?? 30) == 15 ? 'selected' : '' }}>15 minutes</option>
                        <option value="30" {{ ($settings->time_slot_interval ?? 30) == 30 ? 'selected' : '' }}>30 minutes</option>
                        <option value="45" {{ ($settings->time_slot_interval ?? 30) == 45 ? 'selected' : '' }}>45 minutes</option>
                        <option value="60" {{ ($settings->time_slot_interval ?? 30) == 60 ? 'selected' : '' }}>60 minutes (1 hour)</option>
                        <option value="90" {{ ($settings->time_slot_interval ?? 30) == 90 ? 'selected' : '' }}>90 minutes (1.5 hours)</option>
                        <option value="120" {{ ($settings->time_slot_interval ?? 30) == 120 ? 'selected' : '' }}>120 minutes (2 hours)</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Time interval between available booking slots</p>
                </div>

                <!-- Deposit Per Pax -->
                <div>
                    <label for="deposit_per_pax" class="block text-sm font-medium text-gray-700 mb-2">
                        Deposit Per Person (RM) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="deposit_per_pax" name="deposit_per_pax" 
                           value="{{ number_format($settings->deposit_per_pax, 2, '.', '') }}" 
                           step="0.01" min="0" required
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Deposit amount charged per person for each reservation</p>
                </div>

                <!-- Preview Time Slots -->
                <div class="pt-4 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Time Slots Preview</h4>
                    <p class="text-sm text-gray-500 mb-3">Based on your opening hours and interval:</p>
                    <div id="timeSlotsPreview" class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <!-- Time slots will be generated here -->
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Date Settings -->
    <div class="bg-white shadow-sm rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Date Settings</h3>
        </div>
        <div class="p-6">
            <!-- Add/Update Date Setting Form -->
            <form id="dateSettingsForm" class="mb-6">
                <div class="mb-4">
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date <span class="text-red-500">*</span></label>
                    <input type="date" id="date" name="date" required min="{{ date('Y-m-d') }}"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="is_open" name="is_open" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_open" class="ml-2 block text-sm text-gray-900">
                            Open for Reservations
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Toggle to open or close this date for reservations</p>
                </div>
                
                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Save Date Setting
                </button>
            </form>

            <!-- Current Date Settings List -->
            <div class="border-t border-gray-200 pt-4">
                <h4 class="text-sm font-medium text-gray-900 mb-3">Current Date Settings</h4>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($dateSettings as $setting)
                    <div class="flex items-center justify-between p-4 rounded-lg border {{ $setting->is_open ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                        <div>
                            <div class="font-medium text-gray-900">{{ $setting->date->format('l, M d, Y') }}</div>
                            <span class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $setting->is_open ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $setting->is_open ? 'Open' : 'Closed' }}
                            </span>
                        </div>
                        <button onclick="toggleDate('{{ $setting->date->format('Y-m-d') }}', {{ $setting->is_open ? 'false' : 'true' }}, this)"
                                class="px-3 py-1 text-sm rounded-md {{ $setting->is_open ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white">
                            {{ $setting->is_open ? 'Close' : 'Open' }}
                        </button>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-500">
                        <p>No date settings configured.</p>
                        <p class="text-sm mt-1">Dates are open by default.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Restaurant Settings Form
    document.getElementById('restaurantSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const openingTime = document.getElementById('opening_time').value;
        const closingTime = document.getElementById('closing_time').value;
        
        // Validate that time inputs have values
        if (!openingTime || !closingTime) {
            showToast('Please select both opening and closing times', 'error');
            return;
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        setButtonLoading(submitBtn, true, originalText);
        
        const data = {
            opening_time: openingTime,
            closing_time: closingTime,
            deposit_per_pax: parseFloat(document.getElementById('deposit_per_pax').value),
            time_slot_interval: parseInt(document.getElementById('time_slot_interval').value),
        };

        try {
            const response = await fetch('/admin/restaurant-settings/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            setButtonLoading(submitBtn, false, originalText);
            if (result.success) {
                showToast('Settings saved successfully', 'success');
                updateTimeSlotsPreview();
            } else {
                // Show validation errors if available
                let errorMessage = result.message || 'Failed to save settings';
                if (result.errors) {
                    const errorList = Object.values(result.errors).flat().join(', ');
                    errorMessage = errorMessage + ': ' + errorList;
                }
                showToast(errorMessage, 'error');
            }
        } catch (error) {
            setButtonLoading(submitBtn, false, originalText);
            showToast('Error saving settings', 'error');
        }
    });

    // Date Settings Form
    document.getElementById('dateSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        setButtonLoading(submitBtn, true, originalText);
        
        const data = {
            date: document.getElementById('date').value,
            is_open: document.getElementById('is_open').checked
        };

        try {
            const response = await fetch('/admin/restaurant-settings/toggle-date', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            setButtonLoading(submitBtn, false, originalText);
            if (result.success) {
                showToast('Date setting saved successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message || 'Failed to save date setting', 'error');
            }
        } catch (error) {
            setButtonLoading(submitBtn, false, originalText);
            showToast('Error saving date setting', 'error');
        }
    });

    function toggleDate(date, isOpen, button) {
        if (!button) return;
        const originalText = button.innerHTML;
        setButtonLoading(button, true, originalText);

        fetch('/admin/restaurant-settings/toggle-date', {
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
            setButtonLoading(button, false, originalText);
            if (data.success) {
                showToast('Date setting updated successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to update date setting', 'error');
            }
        })
        .catch(error => {
            setButtonLoading(button, false, originalText);
            showToast('Error updating date setting', 'error');
        });
    }

    // Generate time slots preview
    function generateTimeSlots(openingTime, closingTime, interval) {
        const slots = [];
        const opening = new Date(`2000-01-01T${openingTime}`);
        const closing = new Date(`2000-01-01T${closingTime}`);
        let current = new Date(opening);

        while (current < closing) {
            const endTime = new Date(current.getTime() + interval * 60000);
            if (endTime > closing) break;

            const timeStr = current.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
            slots.push({
                value: current.toTimeString().slice(0, 5),
                display: timeStr
            });

            current = new Date(endTime);
        }

        return slots;
    }

    function updateTimeSlotsPreview() {
        const openingTime = document.getElementById('opening_time').value;
        const closingTime = document.getElementById('closing_time').value;
        const interval = parseInt(document.getElementById('time_slot_interval').value);

        if (!openingTime || !closingTime) return;

        const slots = generateTimeSlots(openingTime, closingTime, interval);
        const previewDiv = document.getElementById('timeSlotsPreview');
        
        if (slots.length === 0) {
            previewDiv.innerHTML = '<div class="col-span-3"><p class="text-sm text-gray-500">No time slots available with current settings.</p></div>';
            return;
        }

        previewDiv.innerHTML = slots.map(slot => 
            `<div class="px-3 py-2 text-center text-sm font-medium rounded-md bg-blue-100 text-blue-800">
                ${slot.display}
            </div>`
        ).join('');
    }

    // Update preview when settings change
    document.getElementById('opening_time').addEventListener('change', updateTimeSlotsPreview);
    document.getElementById('closing_time').addEventListener('change', updateTimeSlotsPreview);
    document.getElementById('time_slot_interval').addEventListener('change', updateTimeSlotsPreview);

    // Initial preview
    updateTimeSlotsPreview();
</script>
@endpush
