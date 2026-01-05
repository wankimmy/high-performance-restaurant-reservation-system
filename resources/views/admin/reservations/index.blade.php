@extends('layouts.admin')

@section('title', 'Admin - Reservations')

@section('page-title', '📋 Reservations Management')

@section('content')
<div class="filters">
    <form method="GET" action="{{ route('admin.reservations.index') }}" style="display: inline;">
        <select name="status">
            <option value="">All Status</option>
            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
        </select>
        <input type="date" name="date" value="{{ request('date') }}" placeholder="Filter by date">
        <button type="submit" class="btn" style="background: #667eea; color: white;">Filter</button>
        <a href="{{ route('admin.reservations.index') }}" style="margin-left: 10px; color: #667eea;">Clear</a>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Table</th>
                <th>Pax</th>
                <th>Date</th>
                <th>Time</th>
                <th>Notes</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservations as $reservation)
            <tr>
                <td>{{ $reservation->id }}</td>
                <td>
                    <div><strong>{{ $reservation->customer_name }}</strong></div>
                    <div style="font-size: 12px; color: #666;">{{ $reservation->customer_email }}</div>
                    <div style="font-size: 12px; color: #666;">{{ $reservation->customer_phone }}</div>
                </td>
                <td>{{ $reservation->table->name }}</td>
                <td>{{ $reservation->pax }}</td>
                <td>{{ $reservation->reservation_date->format('M d, Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($reservation->reservation_time)->format('h:i A') }}</td>
                <td>{{ $reservation->notes ?? '-' }}</td>
                <td>
                    <span class="status {{ $reservation->status }}">{{ ucfirst($reservation->status) }}</span>
                </td>
                <td>
                    @if($reservation->status !== 'cancelled')
                    <button class="btn btn-cancel" onclick="cancelReservation({{ $reservation->id }})">
                        Cancel
                    </button>
                    @else
                    <span style="color: #999;">Cancelled</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center; padding: 40px; color: #999;">
                    No reservations found
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    {{ $reservations->links() }}
</div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

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
        messageDiv.textContent = text;
        messageDiv.className = `message ${type}`;
        messageDiv.style.display = 'block';
        
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 3000);
    }
</script>
@endpush
