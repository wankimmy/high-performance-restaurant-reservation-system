@extends('layouts.admin')

@section('title', 'Restaurant Settings')
@section('page-title', 'Restaurant Settings')

@section('content')
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <form id="restaurantSettingsForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Opening Time -->
                <div>
                    <label for="opening_time" class="block text-sm font-medium text-gray-700 mb-1">
                        Opening Time <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="opening_time" name="opening_time" 
                           value="{{ $settings->opening_time }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">Restaurant opening time</p>
                </div>

                <!-- Closing Time -->
                <div>
                    <label for="closing_time" class="block text-sm font-medium text-gray-700 mb-1">
                        Closing Time <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="closing_time" name="closing_time" 
                           value="{{ $settings->closing_time }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">Restaurant closing time</p>
                </div>

                <!-- Time Slot Interval -->
                <div>
                    <label for="time_slot_interval" class="block text-sm font-medium text-gray-700 mb-1">
                        Time Slot Interval (minutes) <span class="text-red-500">*</span>
                    </label>
                    <select id="time_slot_interval" name="time_slot_interval" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                    <label for="deposit_per_pax" class="block text-sm font-medium text-gray-700 mb-1">
                        Deposit Per Person (RM) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="deposit_per_pax" name="deposit_per_pax" 
                           value="{{ number_format($settings->deposit_per_pax, 2, '.', '') }}" 
                           step="0.01" min="0" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">Deposit amount charged per person for each reservation</p>
                </div>
            </div>

            <!-- Preview Time Slots -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Time Slots Preview</h3>
                <p class="text-sm text-gray-500 mb-4">Based on your opening hours and interval, the following time slots will be available:</p>
                <div id="timeSlotsPreview" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                    <!-- Time slots will be generated here -->
                </div>
            </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<div id="message" class="hidden fixed top-4 right-4 z-50"></div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    document.getElementById('restaurantSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const data = {
            opening_time: document.getElementById('opening_time').value,
            closing_time: document.getElementById('closing_time').value,
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

            if (result.success) {
                showMessage('Settings saved successfully', 'success');
                updateTimeSlotsPreview();
            } else {
                showMessage(result.message || 'Failed to save settings', 'error');
            }
        } catch (error) {
            showMessage('Error saving settings', 'error');
        }
    });

    function showMessage(text, type) {
        const messageDiv = document.getElementById('message');
        const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        messageDiv.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg`;
        messageDiv.textContent = text;
        messageDiv.classList.remove('hidden');
        
        setTimeout(() => {
            messageDiv.classList.add('hidden');
        }, 3000);
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
            previewDiv.innerHTML = '<p class="text-sm text-gray-500">No time slots available with current settings.</p>';
            return;
        }

        previewDiv.innerHTML = slots.map(slot => 
            `<div class="bg-blue-50 border border-blue-200 rounded px-3 py-2 text-center text-sm font-medium text-blue-700">
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
