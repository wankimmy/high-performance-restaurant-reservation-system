@extends('layouts.admin')

@section('title', 'Reservations')
@section('page-title', 'Reservations Management')

@section('content')

<!-- Reservations Table -->
<div class="bg-white shadow-sm rounded-lg">
    <div class="px-12 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">All Reservations</h3>
    </div>
    <div class="overflow-hidden">
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
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">#{{ $reservation->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $reservation->customer_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $reservation->customer_phone }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $reservation->customer_email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">{{ $reservation->table->name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $reservation->pax }} {{ $reservation->pax == 1 ? 'guest' : 'guests' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">RM {{ number_format($reservation->deposit_amount ?? 0, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="font-medium">{{ $reservation->reservation_date->format('M d, Y') }}</div>
                            <div class="text-gray-500">{{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <span class="truncate block max-w-xs" title="{{ $reservation->notes }}">{{ $reservation->notes ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($reservation->status === 'confirmed')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Confirmed</span>
                            @elseif($reservation->status === 'pending')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Cancelled</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($reservation->has_arrived)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Arrived</span>
                                @if($reservation->arrived_at)
                                    <div class="text-xs text-gray-500 mt-1">{{ $reservation->arrived_at->format('g:i A') }}</div>
                                @endif
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Not Arrived</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($reservation->status !== 'cancelled')
                                    @if(!$reservation->has_arrived && $reservation->status === 'confirmed')
                                        <button onclick="requestArrivalVerification({{ $reservation->id }})" 
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                                                title="Verify Customer Arrival">
                                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Verify Arrival
                                        </button>
                                    @endif
                                    <button onclick="cancelReservation({{ $reservation->id }})" 
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                                            title="Cancel Reservation">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Cancel
                                    </button>
                                @else
                                    <span class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 bg-gray-100">
                                        Cancelled
                                    </span>
                                @endif
                            </div>
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
</div>

<!-- OTP Verification Modal -->
<div id="otpModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Verify Customer Arrival</h3>
                <button onclick="closeOtpModal()" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-md p-3 mb-4">
                <p class="text-sm text-blue-800">An OTP has been sent to the customer's WhatsApp. Please ask the customer to show you the OTP code and enter it below.</p>
            </div>
            
            <form id="otpVerificationForm">
                <input type="hidden" id="verificationReservationId" name="reservation_id">
                
                <div class="mb-4">
                    <label for="otpCode" class="block text-sm font-medium text-gray-700 mb-2">
                        OTP Code <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="otpCode" name="otp_code" maxlength="6" required
                           class="block w-full text-center text-2xl tracking-widest rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="000000">
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeOtpModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
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
        if ($.fn.DataTable.isDataTable('#reservationsTable')) {
            $('#reservationsTable').DataTable().destroy();
        }
        
        const table = $('#reservationsTable');
        const headerCols = table.find('thead tr th').length;
        
        if (headerCols === 12) {
            table.DataTable({
                responsive: {
                    details: {
                        type: 'column',
                        target: 'tr'
                    }
                },
                order: [[0, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    search: "Search :",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                },
                paging: false,
                info: false,
                columnDefs: [
                    { targets: 11, orderable: false, searchable: false }
                ]
            });
        } else {
            console.warn('Table column count mismatch. Expected 12, found:', headerCols);
        }
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
                showToast('OTP sent to customer WhatsApp. Please ask customer to show the OTP.', 'success');
                currentReservationId = id;
                document.getElementById('verificationReservationId').value = id;
                document.getElementById('otpCode').value = '';
                document.getElementById('otpModal').classList.remove('hidden');
                setTimeout(() => document.getElementById('otpCode').focus(), 300);
            } else {
                showToast(data.message || 'Failed to send OTP', 'error');
            }
        })
        .catch(error => {
            showToast('Error sending OTP', 'error');
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
            showToast('Please enter a 6-digit OTP code', 'error');
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
                showToast('Customer arrival verified successfully!', 'success');
                closeOtpModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message || 'Invalid OTP code', 'error');
            }
        })
        .catch(error => {
            showToast('Error verifying OTP', 'error');
        });
    });

    document.getElementById('otpCode').addEventListener('input', function(e) {
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
                showToast('Reservation cancelled successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to cancel reservation', 'error');
            }
        })
        .catch(error => {
            showToast('Error cancelling reservation', 'error');
        });
    }
</script>
@endpush
