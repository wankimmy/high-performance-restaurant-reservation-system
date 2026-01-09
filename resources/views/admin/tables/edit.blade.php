@extends('layouts.admin')

@section('title', 'Edit Table')
@section('page-title', 'Edit Table')

@section('content')
<div class="bg-white shadow rounded-lg">
    <form method="POST" action="{{ route('admin.tables.update', $table) }}" class="p-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Table Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $table->name) }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-300 @enderror">
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacity (Number of Guests) <span class="text-red-500">*</span></label>
                <input type="number" name="capacity" id="capacity" value="{{ old('capacity', $table->capacity) }}" required min="1" max="20"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('capacity') border-red-300 @enderror">
                @error('capacity')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="is_available" value="1" {{ old('is_available', $table->is_available) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Available for Reservations</span>
                </label>
            </div>
        </div>
        
        <div class="mt-6 flex items-center justify-end gap-4">
            <a href="{{ route('admin.tables.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                Cancel
            </a>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Update Table
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.querySelector('form').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        setButtonLoading(submitBtn, true, originalText);
    });
</script>
@endpush
@endsection
