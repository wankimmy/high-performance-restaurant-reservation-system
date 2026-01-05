@extends('layouts.app')

@section('title', 'Restaurant Reservation')

@section('content')
<div class="container">
    <h1>🍽️ Make a Reservation</h1>
    
    <div id="message" class="message"></div>
    
    <form id="reservationForm">
        <div class="form-group">
            <label for="reservation_date">Date *</label>
            <input type="date" id="reservation_date" name="reservation_date" required min="{{ date('Y-m-d') }}">
        </div>
        
        <div class="form-group">
            <label for="reservation_time">Time *</label>
            <input type="time" id="reservation_time" name="reservation_time" required>
        </div>
        
        <div class="form-group">
            <label>Available Tables</label>
            <button type="button" id="checkAvailability" class="btn" style="margin-bottom: 10px;">Check Availability</button>
            <div id="tablesList" class="tables-list"></div>
            <input type="hidden" id="table_id" name="table_id" required>
        </div>
        
        <div class="form-group">
            <label for="pax">Number of Guests *</label>
            <input type="number" id="pax" name="pax" min="1" max="20" required>
        </div>
        
        <div class="form-group">
            <label for="customer_name">Name *</label>
            <input type="text" id="customer_name" name="customer_name" required>
        </div>
        
        <div class="form-group">
            <label for="customer_email">Email *</label>
            <input type="email" id="customer_email" name="customer_email" required>
        </div>
        
        <div class="form-group">
            <label for="customer_phone">Phone *</label>
            <input type="tel" id="customer_phone" name="customer_phone" required>
        </div>
        
        <div class="form-group">
            <label for="notes">Notes (Optional, max 100 characters)</label>
            <textarea id="notes" name="notes" maxlength="100"></textarea>
            <div class="char-count"><span id="charCount">0</span>/100</div>
        </div>
        
        <button type="submit" class="btn" id="submitBtn">Book Table</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
        const API_BASE = '/api/v1';
        let lastCheckTime = null;
        let checkTimeout = null;

        // CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Character counter
        const notesField = document.getElementById('notes');
        const charCount = document.getElementById('charCount');
        notesField.addEventListener('input', () => {
            charCount.textContent = notesField.value.length;
        });

        // Check availability
        document.getElementById('checkAvailability').addEventListener('click', async function() {
            const date = document.getElementById('reservation_date').value;
            const time = document.getElementById('reservation_time').value;
            
            if (!date || !time) {
                showMessage('Please select both date and time', 'error');
                return;
            }

            // Prevent spam clicking
            const now = Date.now();
            if (lastCheckTime && now - lastCheckTime < 2000) {
                return;
            }
            lastCheckTime = now;

            this.disabled = true;
            this.textContent = 'Checking...';

            try {
                const response = await fetch(`${API_BASE}/availability?date=${date}&time=${time}`);
                const data = await response.json();

                if (data.available && data.tables && data.tables.length > 0) {
                    displayTables(data.tables);
                } else {
                    showMessage(data.message || 'No tables available for this date and time', 'error');
                    document.getElementById('tablesList').style.display = 'none';
                }
            } catch (error) {
                showMessage('Error checking availability. Please try again.', 'error');
            } finally {
                this.disabled = false;
                this.textContent = 'Check Availability';
            }
        });

        function displayTables(tables) {
            const container = document.getElementById('tablesList');
            container.innerHTML = '';
            container.style.display = 'block';

            tables.forEach(table => {
                const div = document.createElement('div');
                div.className = 'table-item';
                div.textContent = `${table.name} (Capacity: ${table.capacity})`;
                div.dataset.tableId = table.id;
                div.dataset.capacity = table.capacity;
                
                div.addEventListener('click', function() {
                    document.querySelectorAll('.table-item').forEach(item => {
                        item.classList.remove('selected');
                    });
                    this.classList.add('selected');
                    document.getElementById('table_id').value = this.dataset.tableId;
                    
                    // Auto-fill pax if not set
                    if (!document.getElementById('pax').value) {
                        document.getElementById('pax').value = this.dataset.capacity;
                    }
                });

                container.appendChild(div);
            });
        }

        // Form submission
        document.getElementById('reservationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.textContent = 'Processing...';

            const formData = new FormData(this);
            const data = Object.fromEntries(formData);

            try {
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
                    showMessage(result.message || 'Reservation request received! We will confirm shortly.', 'success');
                    this.reset();
                    document.getElementById('tablesList').style.display = 'none';
                    document.getElementById('charCount').textContent = '0';
                } else {
                    showMessage(result.message || 'Failed to make reservation. Please try again.', 'error');
                }
            } catch (error) {
                showMessage('Network error. Please check your connection and try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                submitBtn.textContent = 'Book Table';
            }
        });

        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = text;
            messageDiv.className = `message ${type}`;
            messageDiv.style.display = 'block';
            
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }

        // Auto-check availability when date/time changes
        ['reservation_date', 'reservation_time'].forEach(id => {
            document.getElementById(id).addEventListener('change', function() {
                if (checkTimeout) clearTimeout(checkTimeout);
                checkTimeout = setTimeout(() => {
                    if (document.getElementById('reservation_date').value && 
                        document.getElementById('reservation_time').value) {
                        document.getElementById('checkAvailability').click();
                    }
                }, 500);
            });
        });
</script>
@endpush

