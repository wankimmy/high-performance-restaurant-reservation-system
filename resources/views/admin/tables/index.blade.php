@extends('layouts.admin')

@section('title', 'Tables')
@section('page-title', 'Tables Management')

@section('content')
<!-- Filters and Actions -->
<div class="bg-white shadow-sm rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Filters</h3>
    </div>
    <div class="p-6">
        <form method="GET" action="{{ route('admin.tables.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <div>
                <label for="is_available" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="is_available" id="is_available" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Tables</option>
                    <option value="1" {{ request('is_available') == '1' ? 'selected' : '' }}>Available</option>
                    <option value="0" {{ request('is_available') == '0' ? 'selected' : '' }}>Unavailable</option>
                </select>
            </div>
            <div>
                <label for="min_capacity" class="block text-sm font-medium text-gray-700 mb-2">Min Capacity</label>
                <input type="number" name="min_capacity" id="min_capacity" value="{{ request('min_capacity') }}" min="1" max="20" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="max_capacity" class="block text-sm font-medium text-gray-700 mb-2">Max Capacity</label>
                <input type="number" name="max_capacity" id="max_capacity" value="{{ request('max_capacity') }}" min="1" max="20" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Filter
                </button>
                <a href="{{ route('admin.tables.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Clear
                </a>
                <a href="{{ route('admin.tables.create') }}" class="ml-auto inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    Add Table
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tables Table -->
<div class="bg-white shadow-sm rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">All Tables</h3>
    </div>
    <div class="overflow-hidden">
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
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $table->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">{{ $table->capacity }} {{ $table->capacity == 1 ? 'person' : 'people' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
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
                                        <div class="text-xs text-gray-500 mt-1">
                                            Auto-available: {{ $releaseTime->format('M d, g:i A') }}
                                        </div>
                                    @elseif(isset($releaseTime) && $releaseTime->isPast())
                                        <div class="text-xs text-yellow-600 mt-1">
                                            Should be available (checking...)
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="font-medium">{{ $table->reservations_count }}</div>
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.tables.edit', $table) }}" class="text-blue-600 hover:text-blue-900">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                                <button onclick="toggleAvailability({{ $table->id }})" class="text-yellow-600 hover:text-yellow-900">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </button>
                                <button onclick="deleteTable({{ $table->id }})" class="text-red-600 hover:text-red-900">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                            <p class="mb-2">No tables found</p>
                            <a href="{{ route('admin.tables.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Create your first table
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    $(document).ready(function() {
        $('#tablesTable').DataTable({
            responsive: true,
            order: [[0, 'asc']],
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
                showToast('Table availability updated', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to update', 'error');
            }
        })
        .catch(error => showToast('Error updating availability', 'error'));
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
</script>
@endpush
