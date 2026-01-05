@extends('layouts.app')

@section('title', 'Restaurant Reservation')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 py-12 px-4 sm:px-6 lg:px-8">
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
                        <div class="row g-2">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <input type="text" id="reservation_date" name="reservation_date" 
                                           class="form-control" 
                                           placeholder="Select date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select id="reservation_time" name="reservation_time" 
                                        class="form-select" required>
                                    <option value="">Select time</option>
                                    <option value="16:00">4:00 PM</option>
                                    <option value="16:30">4:30 PM</option>
                                    <option value="17:00">5:00 PM</option>
                                    <option value="17:30">5:30 PM</option>
                                    <option value="18:00">6:00 PM</option>
                                    <option value="18:30">6:30 PM</option>
                                    <option value="19:00">7:00 PM</option>
                                    <option value="19:30">7:30 PM</option>
                                    <option value="20:00">8:00 PM</option>
                                    <option value="20:30">8:30 PM</option>
                                    <option value="21:00">9:00 PM</option>
                                    <option value="21:30">9:30 PM</option>
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
                                <option value="">Select number of guests</option>
                                @for($i = 1; $i <= 20; $i++)
                                <option value="{{ $i }}">{{ $i }} {{ $i == 1 ? 'person' : 'people' }}</option>
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

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
        today.setHours(0, 0, 0, 0); // Set to start of today
        
        // Set minimum date to today (disable all past dates)
        const minDate = today;
        
        // No maximum date - show all future dates
        // Convert closed dates to array of Date objects for bootstrap-datepicker
        const disabledDates = closedDatesList.map(date => {
            const dateObj = new Date(date + 'T00:00:00');
            dateObj.setHours(0, 0, 0, 0);
            return dateObj;
        });

        // Initialize Bootstrap Datepicker
        $('#reservation_date').datepicker({
            format: 'MM dd, yyyy',
            startDate: minDate, // Disable all dates before today
            endDate: false, // No end date - show all future dates
            datesDisabled: disabledDates, // Disable dates closed by admin
            autoclose: true,
            todayHighlight: true,
            weekStart: 0,
            orientation: 'bottom auto',
            enableOnReadonly: false,
            clearBtn: false
        }).on('changeDate', function(e) {
            const selected = e.date;
            if (selected) {
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
                
                // Trigger availability check if time and pax are also selected
                if (selectedTime && selectedPax) {
                    checkAvailability();
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
            
            // Create Bootstrap card element
            const card = document.createElement('div');
            card.className = 'card table-box h-100';
            card.style.cursor = 'pointer';
            card.style.transition = 'all 0.3s ease';
            card.dataset.tableId = table.id;
            card.dataset.tableName = table.name;
            card.dataset.tableCapacity = table.capacity;
            card.dataset.isPerfectFit = isPerfectFit;
            
            // Set initial border and background
            if (isPerfectFit) {
                card.classList.add('border-success', 'bg-light');
                card.style.borderWidth = '2px';
            } else {
                card.classList.add('border-secondary');
                card.style.borderWidth = '2px';
            }
            
            // Card body
            const cardBody = document.createElement('div');
            cardBody.className = 'card-body text-center p-3';
            
            // Table name
            const tableName = document.createElement('h6');
            tableName.className = 'card-title mb-2 fw-bold';
            tableName.textContent = table.name;
            
            // Capacity
            const capacity = document.createElement('p');
            capacity.className = 'card-text mb-1 small text-muted';
            capacity.textContent = `Capacity: ${table.capacity}`;
            
            // Perfect fit badge
            if (isPerfectFit) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-success mt-1';
                badge.textContent = 'Perfect fit!';
                cardBody.appendChild(tableName);
                cardBody.appendChild(capacity);
                cardBody.appendChild(badge);
            } else {
                cardBody.appendChild(tableName);
                cardBody.appendChild(capacity);
            }
            
            card.appendChild(cardBody);
            
            // Hover effect
            card.addEventListener('mouseenter', function() {
                if (!this.classList.contains('border-primary')) {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                if (!this.classList.contains('border-primary')) {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                }
            });
            
            // Click handler
            card.addEventListener('click', function() {
                // Remove selected state from all tables
                document.querySelectorAll('.table-box').forEach(box => {
                    const wasPerfectFit = box.dataset.isPerfectFit === 'true';
                    box.classList.remove('border-primary', 'bg-primary', 'border-success', 'bg-light', 'border-secondary');
                    box.style.borderWidth = '2px';
                    box.style.transform = 'translateY(0)';
                    box.style.boxShadow = '';
                    
                    // Restore original text colors
                    const cardBody = box.querySelector('.card-body');
                    if (cardBody) {
                        cardBody.classList.remove('text-white');
                        const cardTitle = cardBody.querySelector('.card-title');
                        const cardText = cardBody.querySelector('.card-text');
                        const badge = cardBody.querySelector('.badge');
                        
                        if (cardTitle) {
                            cardTitle.classList.remove('text-white');
                            cardTitle.classList.add('text-dark');
                        }
                        if (cardText) {
                            cardText.classList.remove('text-white');
                            cardText.classList.add('text-muted');
                        }
                        if (badge) {
                            badge.classList.remove('bg-light', 'text-primary');
                            badge.classList.add('bg-success');
                        }
                    }
                    
                    // Restore original styling
                    if (wasPerfectFit) {
                        box.classList.add('border-success', 'bg-light');
                    } else {
                        box.classList.add('border-secondary');
                    }
                });
                
                // Add selected state to clicked table
                this.classList.remove('border-success', 'bg-light', 'border-secondary');
                this.classList.add('border-primary', 'bg-primary');
                this.style.borderWidth = '3px';
                this.style.boxShadow = '0 4px 12px rgba(13, 110, 253, 0.3)';
                
                // Update text colors in card body for selected state
                const cardBody = this.querySelector('.card-body');
                if (cardBody) {
                    cardBody.classList.add('text-white');
                    const cardTitle = cardBody.querySelector('.card-title');
                    const cardText = cardBody.querySelector('.card-text');
                    const badge = cardBody.querySelector('.badge');
                    
                    if (cardTitle) {
                        cardTitle.classList.remove('text-dark');
                        cardTitle.classList.add('text-white');
                    }
                    if (cardText) {
                        cardText.classList.remove('text-muted');
                        cardText.classList.add('text-white');
                    }
                    if (badge) {
                        badge.classList.remove('bg-success');
                        badge.classList.add('bg-light', 'text-primary');
                    }
                }
                
                // Store selected table data
                selectedTableData = {
                    id: table.id,
                    name: table.name,
                    capacity: table.capacity
                };
                
                // Set selected table ID
                document.getElementById('selected_table_id').value = table.id;
                
                // Show customer info
                document.getElementById('customerInfo').classList.remove('hidden');
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
        selectedTableData = null;

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
        const submitText = document.getElementById('submitText');
        submitBtn.disabled = true;
        submitText.textContent = 'Sending OTP...';

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

            if (response.ok || response.status === 202) {
                // Redirect to OTP verification page
                window.location.href = `/verify-otp?session_id=${result.session_id}&reservation_id=${result.reservation_id || ''}`;
            } else {
                showMessage(result.message || 'Failed to make reservation. Please try again.', 'error');
                submitBtn.disabled = false;
                submitText.textContent = 'Confirm Reservation';
            }
        } catch (error) {
            showMessage('Network error. Please check your connection and try again.', 'error');
            submitBtn.disabled = false;
            submitText.textContent = 'Confirm Reservation';
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
</script>
@endpush
