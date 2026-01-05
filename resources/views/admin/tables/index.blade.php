@extends('layouts.admin')

@section('title', 'Tables')
@section('page-title', 'Tables Management')

@section('content')
<!-- Filters and Actions -->
<div class="bg-white shadow rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <form method="GET" action="{{ route('admin.tables.index') }}" class="flex flex-col md:flex-row gap-4 flex-1">
                <div>
                    <label for="is_available" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="is_available" id="is_available" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Tables</option>
                        <option value="1" {{ request('is_available') == '1' ? 'selected' : '' }}>Available</option>
                        <option value="0" {{ request('is_available') == '0' ? 'selected' : '' }}>Unavailable</option>
                    </select>
                </div>
                <div>
                    <label for="min_capacity" class="block text-sm font-medium text-gray-700 mb-1">Min Capacity</label>
                    <input type="number" name="min_capacity" id="min_capacity" value="{{ request('min_capacity') }}" min="1" max="20" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="max_capacity" class="block text-sm font-medium text-gray-700 mb-1">Max Capacity</label>
                    <input type="number" name="max_capacity" id="max_capacity" value="{{ request('max_capacity') }}" min="1" max="20" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Filter</button>
                    <a href="{{ route('admin.tables.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">Clear</a>
                </div>
            </form>
            <a href="{{ route('admin.tables.create') }}" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 whitespace-nowrap">
                + Add Table
            </a>
        </div>
    </div>
</div>

<!-- Tables Table -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table id="tablesTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Table Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservations</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($tables as $table)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $table->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $table->capacity }} {{ $table->capacity == 1 ? 'person' : 'people' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $table->is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $table->is_available ? 'Available' : 'Unavailable' }}
                            </span>
                            @if(!$table->is_available && $table->reservations && $table->reservations->count() > 0)
                                @php
                                    $nextReservation = $table->reservations->first();
                                    if ($nextReservation) {
                                        $reservationDateTime = \Carbon\Carbon::parse($nextReservation->reservation_date->format('Y-m-d') . ' ' . $nextReservation->reservation_time);
                                        $releaseTime = $reservationDateTime->copy()->addHour();
                                    }
                                @endphp
                                @if(isset($releaseTime) && $releaseTime->isFuture())
                                    <span class="text-xs text-gray-500 mt-1">
                                        Auto-available: {{ $releaseTime->format('M d, g:i A') }}
                                    </span>
                                @elseif(isset($releaseTime) && $releaseTime->isPast())
                                    <span class="text-xs text-orange-500 mt-1">
                                        Should be available (checking...)
                                    </span>
                                @endif
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $table->reservations_count }}</div>
                        @if($table->reservations && $table->reservations->count() > 0)
                            @php
                                $nextReservation = $table->reservations->first();
                            @endphp
                            @if($nextReservation)
                                <div class="text-xs text-gray-500">
                                    Next: {{ \Carbon\Carbon::parse($nextReservation->reservation_date->format('Y-m-d') . ' ' . $nextReservation->reservation_time)->format('M d, g:i A') }}
                                </div>
                            @endif
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <a href="{{ route('admin.tables.edit', $table) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                        <button onclick="toggleAvailability({{ $table->id }})" class="text-gray-600 hover:text-gray-900">
                            {{ $table->is_available ? 'Make Unavailable' : 'Make Available' }}
                        </button>
                        <button onclick="deleteTable({{ $table->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                        <p class="mb-2">No tables found</p>
                        <a href="{{ route('admin.tables.create') }}" class="text-indigo-600 hover:text-indigo-900">Create your first table</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="message" class="hidden fixed top-4 right-4 z-50"></div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Initialize DataTables
    $(document).ready(function() {
        $('#tablesTable').DataTable({
            responsive: true,
            order: [[0, 'asc']], // Sort by table name ascending
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                search: "Search all columns:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
            }
        });
    });

    function toggleAvailability(id) {
        fetch(`/admin/tables/${id}/toggle-availability`, {
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
                showMessage('Table availability updated', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showMessage(data.message || 'Failed to update', 'error');
            }
        })
        .catch(error => showMessage('Error updating availability', 'error'));
    }

    function deleteTable(id) {
        if (!confirm('Are you sure you want to delete this table? This action cannot be undone.')) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/tables/${id}`;
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = csrfToken;
        form.appendChild(csrf);
        
        const method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'DELETE';
        form.appendChild(method);
        
        document.body.appendChild(form);
        form.submit();
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
