@extends('layouts.admin')

@section('title', 'Reservations')
@section('page-title', 'Reservations Management')

@section('content')
<!-- Filters -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        <form method="GET" action="{{ route('admin.reservations.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Status</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="date" name="date" id="date" value="{{ request('date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Filter
                </button>
                <a href="{{ route('admin.reservations.index') }}" class="w-full bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 text-center">
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Reservations Table -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table id="reservationsTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Table</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guests</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deposit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arrival</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($reservations as $reservation)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ $reservation->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $reservation->customer_name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $reservation->customer_phone }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $reservation->customer_email }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $reservation->table->name }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $reservation->pax }} {{ $reservation->pax == 1 ? 'guest' : 'guests' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">RM {{ number_format($reservation->deposit_amount ?? 0, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div>{{ $reservation->reservation_date->format('M d, Y') }}</div>
                        <div class="text-gray-500">{{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate" title="{{ $reservation->notes }}">{{ $reservation->notes ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $reservation->status === 'confirmed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $reservation->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $reservation->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($reservation->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($reservation->has_arrived)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Arrived
                            </span>
                            @if($reservation->arrived_at)
                                <div class="text-xs text-gray-500 mt-1">{{ $reservation->arrived_at->format('g:i A') }}</div>
                            @endif
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Not Arrived
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        @if($reservation->status !== 'cancelled')
                            @if(!$reservation->has_arrived && $reservation->status === 'confirmed')
                                <button onclick="requestArrivalVerification({{ $reservation->id }})" class="text-green-600 hover:text-green-900" title="Verify Attendance">
                                    ✓ Verify
                                </button>
                            @endif
                            <button onclick="cancelReservation({{ $reservation->id }})" class="text-red-600 hover:text-red-900">
                                Cancel
                            </button>
                        @else
                            <span class="text-gray-400">Cancelled</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="px-6 py-4 text-center text-sm text-gray-500">No reservations found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="message" class="hidden fixed top-4 right-4 z-50"></div>

<!-- OTP Verification Modal -->
<div id="otpModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Verify Customer Arrival</h3>
            <p class="text-sm text-gray-600 mb-4">An OTP has been sent to the customer's WhatsApp. Please ask the customer to show you the OTP code and enter it below.</p>
            
            <form id="otpVerificationForm">
                <input type="hidden" id="verificationReservationId" name="reservation_id">
                
                <div class="mb-4">
                    <label for="otpCode" class="block text-sm font-medium text-gray-700 mb-1">
                        OTP Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="otpCode" name="otp_code" maxlength="6" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-center text-2xl tracking-widest"
                           placeholder="000000">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeOtpModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Verify
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Initialize DataTables
    $(document).ready(function() {
        $('#reservationsTable').DataTable({
            responsive: true,
            order: [[0, 'desc']], // Sort by ID descending
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                search: "Search all columns:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
            }
        });
    });

    let currentReservationId = null;

    function requestArrivalVerification(id) {
        if (!confirm('Send OTP to customer for arrival verification?')) {
            return;
        }

        fetch(`/admin/reservations/${id}/request-arrival-verification`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('OTP sent to customer WhatsApp. Please ask customer to show the OTP.', 'success');
                currentReservationId = id;
                document.getElementById('verificationReservationId').value = id;
                document.getElementById('otpCode').value = '';
                document.getElementById('otpModal').classList.remove('hidden');
            } else {
                showMessage(data.message || 'Failed to send OTP', 'error');
            }
        })
        .catch(error => {
            showMessage('Error sending OTP', 'error');
        });
    }

    function closeOtpModal() {
        document.getElementById('otpModal').classList.add('hidden');
        document.getElementById('otpCode').value = '';
        currentReservationId = null;
    }

    document.getElementById('otpVerificationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const otpCode = document.getElementById('otpCode').value;
        const reservationId = document.getElementById('verificationReservationId').value;

        if (otpCode.length !== 6) {
            showMessage('Please enter a 6-digit OTP code', 'error');
            return;
        }

        fetch(`/admin/reservations/${reservationId}/verify-arrival-otp`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                otp_code: otpCode
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Customer arrival verified successfully!', 'success');
                closeOtpModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(data.message || 'Invalid OTP code', 'error');
            }
        })
        .catch(error => {
            showMessage('Error verifying OTP', 'error');
        });
    });

    // Auto-focus OTP input when modal opens
    document.getElementById('otpCode').addEventListener('input', function(e) {
        // Only allow numbers
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });

    function cancelReservation(id) {
        if (!confirm('Are you sure you want to cancel this reservation?')) {
            return;
        }

        fetch(`/admin/reservations/${id}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Reservation cancelled successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage(data.message || 'Failed to cancel reservation', 'error');
            }
        })
        .catch(error => {
            showMessage('Error cancelling reservation', 'error');
        });
    }

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
</script>
@endpush
