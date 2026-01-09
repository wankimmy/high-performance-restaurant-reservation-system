@extends('layouts.app')

@section('title', 'Restaurant Reservation')

@section('content')
<div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <!-- Alert Messages -->
        <div id="message" class="hidden mb-6 p-4 rounded-lg" role="alert"></div>

        <!-- Booking Form Card -->
        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-8 text-center">
                <h2 class="text-3xl font-bold text-white">Restaurant Reservation System</h2>
                <p class="mt-2 text-indigo-100">Book your table in just a few steps</p>
            </div>
            
            <div class="p-6 sm:p-8">
                <form id="reservationForm" class="space-y-6">
                    <!-- Date & Time Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Date & Time <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="md:col-span-2">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <input type="text" id="reservation_date" name="reservation_date" 
                                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" 
                                           placeholder="Select date" required>
                                </div>
                            </div>
                            <div>
                                <select id="reservation_time" name="reservation_time" 
                                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" 
                                        required>
                                    <option value="">Select time</option>
                                    <!-- Time slots will be dynamically loaded -->
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Pax Selection -->
                    <div>
                        <label for="pax" class="block text-sm font-medium text-gray-700 mb-2">
                            Number of Guests <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-3-3h-4a3 3 0 00-3 3v2h5zM13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <select id="pax" name="pax" 
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" 
                                    required>
                                @for($i = 1; $i <= 10; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <!-- Table Selection -->
                    <div id="tableSelection">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Table <span class="text-red-500">*</span>
                        </label>
                        <p class="mb-4 text-sm text-gray-500">
                            <span id="tableCount">Please select date, time, and number of guests to see available tables</span>
                        </p>
                        <div id="tablesGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 border-2 border-gray-200 rounded-lg p-4 bg-gray-50">
                            <!-- Tables will be displayed here -->
                        </div>
                        <input type="hidden" id="selected_table_id" name="table_id" required>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Special Requests (Optional)
                        </label>
                        <textarea id="notes" name="notes" maxlength="100" rows="3" 
                                  class="block w-full border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" 
                                  placeholder="Any special requests or dietary requirements..."></textarea>
                        <div class="mt-1 text-sm text-gray-500 text-right">
                            <span id="charCount">0</span>/100 characters
                        </div>
                    </div>

                    <!-- Deposit Information -->
                    <div id="depositInfo" class="hidden pt-4 border-t border-gray-200">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Deposit Required</p>
                                    <p class="text-xs text-gray-500 mt-1">This deposit will be charged per person</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-indigo-600" id="depositAmount">-</p>
                                    <p class="text-xs text-gray-500 mt-1" id="depositBreakdown"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div id="customerInfo" class="hidden pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Your Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="customer_name" name="customer_name" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" 
                                       required>
                            </div>
                            <div>
                                <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" id="customer_email" name="customer_email" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" 
                                       required>
                            </div>
                            <div class="md:col-span-2">
                                <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Phone <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" id="customer_phone" name="customer_phone" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" id="submitBtn" 
                                class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                            <span id="submitText">Confirm Reservation</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const API_BASE = '/api/v1';
    let selectedDate = '';
    let selectedTime = '';
    let selectedPax = '';
    let closedDates = [];
    let availabilityChecked = false;
    let depositPerPax = 0;
    let timeSlots = [];
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Fetch restaurant settings (optionally for a specific date)
    async function fetchRestaurantSettings(date = null) {
        try {
            const url = date ? `${API_BASE}/restaurant-settings?date=${date}` : `${API_BASE}/restaurant-settings`;
            const response = await fetch(url);
            const data = await response.json();
            if (data.success && data.settings) {
                depositPerPax = parseFloat(data.settings.deposit_per_pax) || 0;
                updateDepositDisplay();
            }
        } catch (error) {
            console.error('Error fetching restaurant settings:', error);
        }
    }

    // Fetch time slots (optionally for a specific date)
    async function fetchTimeSlots(date = null) {
        try {
            const url = date ? `${API_BASE}/time-slots?date=${date}` : `${API_BASE}/time-slots`;
            const response = await fetch(url);
            const data = await response.json();
            if (data.success && data.time_slots) {
                timeSlots = data.time_slots;
                populateTimeSlots();
            }
        } catch (error) {
            console.error('Error fetching time slots:', error);
            // Fallback to default time slots if API fails
            populateDefaultTimeSlots();
        }
    }

    // Populate time slots dropdown
    function populateTimeSlots() {
        const timeSelect = document.getElementById('reservation_time');
        // Clear existing options except the first one
        timeSelect.innerHTML = '<option value="">Select time</option>';
        
        if (timeSlots.length === 0) {
            populateDefaultTimeSlots();
            return;
        }

        timeSlots.forEach(slot => {
            const option = document.createElement('option');
            option.value = slot.value || slot.start_time;
            option.textContent = slot.display || slot.start_time;
            timeSelect.appendChild(option);
        });
    }

    // Fallback default time slots
    function populateDefaultTimeSlots() {
        const timeSelect = document.getElementById('reservation_time');
        const defaultSlots = [
            { value: '16:00', display: '4:00 PM' },
            { value: '16:30', display: '4:30 PM' },
            { value: '17:00', display: '5:00 PM' },
            { value: '17:30', display: '5:30 PM' },
            { value: '18:00', display: '6:00 PM' },
            { value: '18:30', display: '6:30 PM' },
            { value: '19:00', display: '7:00 PM' },
            { value: '19:30', display: '7:30 PM' },
            { value: '20:00', display: '8:00 PM' },
            { value: '20:30', display: '8:30 PM' },
            { value: '21:00', display: '9:00 PM' },
            { value: '21:30', display: '9:30 PM' },
        ];

        defaultSlots.forEach(slot => {
            const option = document.createElement('option');
            option.value = slot.value;
            option.textContent = slot.display;
            timeSelect.appendChild(option);
        });
    }

    // Calculate and display deposit
    function updateDepositDisplay() {
        const depositInfo = document.getElementById('depositInfo');
        const depositAmount = document.getElementById('depositAmount');
        const depositBreakdown = document.getElementById('depositBreakdown');
        
        // Only show deposit if we have both pax selected and deposit per pax from settings
        if (selectedPax && depositPerPax > 0) {
            const totalDeposit = depositPerPax * parseInt(selectedPax);
            depositAmount.textContent = `RM ${totalDeposit.toFixed(2)}`;
            depositBreakdown.textContent = `RM ${depositPerPax.toFixed(2)} × ${selectedPax} ${selectedPax == 1 ? 'person' : 'people'}`;
            depositInfo.classList.remove('hidden');
        } else if (selectedPax && depositPerPax === 0) {
            // If deposit is 0 from settings, hide the deposit info
            depositInfo.classList.add('hidden');
        } else {
            // No pax selected yet, keep hidden
            depositInfo.classList.add('hidden');
            depositAmount.textContent = '-';
            depositBreakdown.textContent = '';
        }
    }

    async function fetchClosedDates() {
        try {
            const response = await fetch(`${API_BASE}/closed-dates`);
            const data = await response.json();
            if (data.success && data.closed_dates) {
                closedDates = data.closed_dates;
                return closedDates;
            }
        } catch (error) {
            console.error('Error fetching closed dates:', error);
        }
        return [];
    }

    async function initDateTimePicker() {
        const closedDatesList = await fetchClosedDates();
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Convert closed dates to array of date strings for Flatpickr
        const disabledDates = closedDatesList.map(date => date);

        // Initialize Flatpickr (Tailwind-compatible datepicker)
        const dateInput = document.getElementById('reservation_date');
        const flatpickrInstance = flatpickr(dateInput, {
            minDate: 'today', // Disable all dates before today
            disable: disabledDates, // Disable dates closed by admin
            dateFormat: 'M d, Y',
            altInput: true,
            altFormat: 'M d, Y',
            allowInput: false,
            clickOpens: true,
            defaultDate: null,
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length > 0) {
                    const selected = selectedDates[0];
                    // Format date as YYYY-MM-DD for API
                    const year = selected.getFullYear();
                    const month = String(selected.getMonth() + 1).padStart(2, '0');
                    const day = String(selected.getDate()).padStart(2, '0');
                    const dateStr = `${year}-${month}-${day}`;
                    selectedDate = dateStr;
                    
                    // Clear tables when date changes
                    availabilityChecked = false;
                    document.getElementById('tablesGrid').innerHTML = '';
                    document.getElementById('selected_table_id').value = '';
                    document.getElementById('customerInfo').classList.add('hidden');
                    selectedTableData = null;
                    
                    // Fetch time slots and settings for the selected date
                    fetchTimeSlots(dateStr);
                    fetchRestaurantSettings(dateStr);
                    
                    // Trigger availability check if time and pax are also selected
                    if (selectedTime && selectedPax) {
                        checkAvailability();
                    }
                }
            }
        });

        // Handle time selection change
        document.getElementById('reservation_time').addEventListener('change', function() {
            selectedTime = this.value;
            
            // Clear tables when time changes
            availabilityChecked = false;
            document.getElementById('tablesGrid').innerHTML = '';
            document.getElementById('selected_table_id').value = '';
            document.getElementById('customerInfo').classList.add('hidden');
            selectedTableData = null;
            
            // Trigger availability check if date and pax are also selected
            if (selectedDate && selectedPax) {
                checkAvailability();
            }
        });
    }

    async function checkAvailability() {
        if (!selectedDate || !selectedTime || !selectedPax) {
            return;
        }

        // Show loading state
        const tablesGrid = document.getElementById('tablesGrid');
        tablesGrid.innerHTML = '<div class="text-gray-500">Checking availability...</div>';

        try {
            const response = await fetch(`${API_BASE}/availability?date=${selectedDate}&time=${selectedTime}&pax=${selectedPax}`);
            const data = await response.json();

            if (data.available && data.tables && data.tables.length > 0) {
                displayTables(data.tables);
                availabilityChecked = true;
                
                // Update table count message
                const tableCountEl = document.getElementById('tableCount');
                if (tableCountEl) {
                    const count = data.count || data.tables.length;
                    tableCountEl.textContent = `${count} ${count === 1 ? 'table' : 'tables'} available`;
                }
                
                // Show success message if multiple tables available
                if (data.count > 1) {
                    showMessage(`${data.count} tables available for ${selectedPax} ${selectedPax == 1 ? 'person' : 'people'}`, 'success');
                }
            } else {
                showMessage(data.message || `No tables available for ${selectedPax} ${selectedPax == 1 ? 'person' : 'people'}. Please select a different time or number of guests.`, 'error');
                document.getElementById('customerInfo').classList.add('hidden');
                document.getElementById('selected_table_id').value = '';
                tablesGrid.innerHTML = '<div class="col-span-2 sm:col-span-3 md:col-span-4 lg:col-span-6 text-center py-8 text-gray-500">No tables available</div>';
            }
        } catch (error) {
            showMessage('Error checking availability. Please try again.', 'error');
            tablesGrid.innerHTML = '<div class="col-span-2 sm:col-span-3 md:col-span-4 lg:col-span-6 text-center py-8 text-gray-500">Error loading tables</div>';
        }
    }

    let selectedTableData = null;

    function displayTables(tables) {
        const tablesGrid = document.getElementById('tablesGrid');
        tablesGrid.innerHTML = '';
        selectedTableData = null;

        tables.forEach(table => {
            const isPerfectFit = table.capacity == selectedPax;
            
            // Create Tailwind card element
            const card = document.createElement('div');
            card.className = 'table-box bg-white rounded-lg border-2 p-4 text-center cursor-pointer transition-all duration-300';
            card.dataset.tableId = table.id;
            card.dataset.tableName = table.name;
            card.dataset.tableCapacity = table.capacity;
            card.dataset.isPerfectFit = isPerfectFit;
            
            // Set initial border and background
            if (isPerfectFit) {
                card.classList.add('border-green-500', 'bg-green-50');
            } else {
                card.classList.add('border-gray-300');
            }
            
            // Table name
            const tableName = document.createElement('h6');
            tableName.className = 'text-lg font-semibold mb-2 text-gray-900';
            tableName.textContent = table.name;
            
            // Capacity
            const capacity = document.createElement('p');
            capacity.className = 'text-sm text-gray-600 mb-2';
            capacity.textContent = `Capacity: ${table.capacity}`;
            
            // Perfect fit badge
            if (isPerfectFit) {
                const badge = document.createElement('span');
                badge.className = 'inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800';
                badge.textContent = 'Perfect fit!';
                card.appendChild(tableName);
                card.appendChild(capacity);
                card.appendChild(badge);
            } else {
                card.appendChild(tableName);
                card.appendChild(capacity);
            }
            
            // Hover effect
            card.addEventListener('mouseenter', function() {
                if (!this.classList.contains('border-blue-500')) {
                    this.classList.add('shadow-md', '-translate-y-1');
                }
            });
            
            card.addEventListener('mouseleave', function() {
                if (!this.classList.contains('border-blue-500')) {
                    this.classList.remove('shadow-md', '-translate-y-1');
                }
            });
            
            // Click handler
            card.addEventListener('click', function() {
                // Remove selected state from all tables
                document.querySelectorAll('.table-box').forEach(box => {
                    const wasPerfectFit = box.dataset.isPerfectFit === 'true';
                    box.classList.remove('border-blue-500', 'bg-blue-500', 'border-green-500', 'bg-green-50', 'border-gray-300', 'shadow-lg');
                    box.classList.remove('shadow-md', '-translate-y-1');
                    
                    // Restore original text colors
                    const title = box.querySelector('h6');
                    const text = box.querySelector('p');
                    const badge = box.querySelector('span');
                    
                    if (title) {
                        title.classList.remove('text-white');
                        title.classList.add('text-gray-900');
                    }
                    if (text) {
                        text.classList.remove('text-white');
                        text.classList.add('text-gray-600');
                    }
                    if (badge) {
                        badge.classList.remove('bg-white', 'text-blue-600');
                        badge.classList.add('bg-green-100', 'text-green-800');
                    }
                    
                    // Restore original styling
                    if (wasPerfectFit) {
                        box.classList.add('border-green-500', 'bg-green-50');
                    } else {
                        box.classList.add('border-gray-300');
                    }
                });
                
                // Add selected state to clicked table
                this.classList.remove('border-green-500', 'bg-green-50', 'border-gray-300');
                this.classList.add('border-blue-500', 'bg-blue-500', 'shadow-lg');
                
                // Update text colors for selected state
                const title = this.querySelector('h6');
                const text = this.querySelector('p');
                const badge = this.querySelector('span');
                
                if (title) {
                    title.classList.remove('text-gray-900');
                    title.classList.add('text-white');
                }
                if (text) {
                    text.classList.remove('text-gray-600');
                    text.classList.add('text-white');
                }
                if (badge) {
                    badge.classList.remove('bg-green-100', 'text-green-800');
                    badge.classList.add('bg-white', 'text-blue-600');
                }
                
                // Store selected table data
                selectedTableData = {
                    id: table.id,
                    name: table.name,
                    capacity: table.capacity
                };
                
                // Set selected table ID
                document.getElementById('selected_table_id').value = table.id;
                
                // Show customer info and deposit
                document.getElementById('customerInfo').classList.remove('hidden');
                updateDepositDisplay();
                document.getElementById('customerInfo').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
            
            tablesGrid.appendChild(card);
        });
    }

    document.getElementById('pax').addEventListener('change', function() {
        selectedPax = this.value;
        
        availabilityChecked = false;
        document.getElementById('tablesGrid').innerHTML = '';
        document.getElementById('selected_table_id').value = '';
        document.getElementById('customerInfo').classList.add('hidden');
        document.getElementById('depositInfo').classList.add('hidden');
        selectedTableData = null;

        // Update deposit display
        updateDepositDisplay();

        if (selectedDate && selectedTime && selectedPax) {
            checkAvailability();
        }
    });


    document.getElementById('notes').addEventListener('input', function() {
        document.getElementById('charCount').textContent = this.value.length;
    });

    document.getElementById('reservationForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!selectedDate || !selectedTime || !selectedPax) {
            showMessage('Please select date, time, and number of guests.', 'error');
            return;
        }

        const tableId = document.getElementById('selected_table_id').value;
        if (!tableId) {
            showMessage('Please select a table.', 'error');
            return;
        }
        
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        setButtonLoading(submitBtn, true, originalText);

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.reservation_date = selectedDate;
        data.reservation_time = selectedTime;
        data.table_id = tableId;
        data.pax = selectedPax;

        try {
            // Step 1: Submit reservation and get OTP
            const response = await fetch(`${API_BASE}/reservations`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.status === 202 || (response.ok && result.success)) {
                // Reservation is being processed asynchronously
                // Redirect to queue page to wait for processing
                window.location.href = `/queue?session_id=${result.session_id}`;
            } else {
                setButtonLoading(submitBtn, false, originalText);
                showMessage(result.message || 'Failed to make reservation. Please try again.', 'error');
            }
        } catch (error) {
            setButtonLoading(submitBtn, false, originalText);
            showMessage('Network error. Please check your connection and try again.', 'error');
        }
    });


    function showMessage(text, type) {
        const messageDiv = document.getElementById('message');
        const bgColor = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
        messageDiv.className = `${bgColor} border px-4 py-3 rounded-lg`;
        messageDiv.textContent = text;
        messageDiv.classList.remove('hidden');
        
        // Auto-hide after 5 seconds (3 seconds for success messages)
        const hideDelay = type === 'success' ? 3000 : 5000;
        setTimeout(() => {
            messageDiv.classList.add('hidden');
        }, hideDelay);
    }

    initDateTimePicker();
    fetchRestaurantSettings();
    fetchTimeSlots();
</script>
@endpush
