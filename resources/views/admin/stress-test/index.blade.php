@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Stress Test</h1>
            <p class="mt-1 text-sm text-gray-600">Test application performance under load</p>
        </div>

        <!-- Server Type Badge -->
        <div class="mb-6">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $serverType === 'swoole' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                </svg>
                Server Type: <strong>{{ strtoupper($serverType) }}</strong>
            </span>
        </div>

        <!-- Test Configuration Form -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Test Configuration</h2>
            </div>
            <div class="px-6 py-4">
                <form id="stressTestForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Requests Per Second -->
                        <div>
                            <label for="requests_per_second" class="block text-sm font-medium text-gray-700 mb-2">
                                Requests Per Second (per endpoint)
                            </label>
                            <input type="number" 
                                   id="requests_per_second" 
                                   name="requests_per_second" 
                                   min="1" 
                                   max="1000" 
                                   value="10" 
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            <p class="mt-1 text-xs text-gray-500">Between 1 and 1000 requests per second for each selected endpoint</p>
                        </div>

                        <!-- Duration -->
                        <div>
                            <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">
                                Duration (seconds)
                            </label>
                            <input type="number" 
                                   id="duration" 
                                   name="duration" 
                                   min="5" 
                                   max="300" 
                                   value="30" 
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                            <p class="mt-1 text-xs text-gray-500">Between 5 and 300 seconds</p>
                        </div>
                    </div>

                    <!-- Endpoints Selection -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Select Endpoints to Test
                        </label>
                        <p class="text-xs text-gray-500 mb-3">Each selected endpoint will be tested independently at the specified requests per second</p>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="endpoints[]" value="/" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700">Homepage (Booking Page)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="endpoints[]" value="/api/v1/restaurant-settings" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700">API: Restaurant Settings</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="endpoints[]" value="/api/v1/time-slots" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700">API: Time Slots</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="endpoints[]" value="/api/v1/availability" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700">API: Availability Check</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="endpoints[]" value="/admin/dashboard" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700">Admin Dashboard</span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-6">
                        <button type="submit" 
                                id="runTestBtn"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg id="runTestSpinner" class="hidden animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Run Stress Test
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Table -->
        <div id="resultsSection" class="hidden bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Test Results</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Endpoint</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Requests</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Failed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate Limited</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RPS (Actual)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Response (ms)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Min (ms)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max (ms)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">P90 (ms)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">P99 (ms)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error Rate</th>
                        </tr>
                    </thead>
                    <tbody id="resultsTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Results will be populated here -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="hidden mt-4 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error</h3>
                    <div class="mt-2 text-sm text-red-700" id="errorText"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('stressTestForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = e.target;
    const runTestBtn = document.getElementById('runTestBtn');
    const runTestSpinner = document.getElementById('runTestSpinner');
    const resultsSection = document.getElementById('resultsSection');
    const errorMessage = document.getElementById('errorMessage');
    const resultsTableBody = document.getElementById('resultsTableBody');
    
    // Get form data
    const formData = new FormData(form);
    const endpoints = Array.from(form.querySelectorAll('input[name="endpoints[]"]:checked')).map(cb => cb.value);
    
    if (endpoints.length === 0) {
        alert('Please select at least one endpoint to test');
        return;
    }
    
    const data = {
        requests_per_second: parseInt(formData.get('requests_per_second')),
        duration: parseInt(formData.get('duration')),
        endpoints: endpoints
    };
    
    // Show loading state
    runTestBtn.disabled = true;
    runTestSpinner.classList.remove('hidden');
    resultsSection.classList.add('hidden');
    errorMessage.classList.add('hidden');
    
    try {
        const response = await fetch('{{ route("admin.stress-test.run") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Display results
            resultsTableBody.innerHTML = '';
            
            result.results.forEach(item => {
                if (item.success) {
                    const data = item.data;
                    const row = document.createElement('tr');
                    row.className = data.error_rate > 5 ? 'bg-red-50' : '';
                    
                    // Format status codes for display
                    let statusCodesText = 'N/A';
                    if (data.status_codes && Object.keys(data.status_codes).length > 0) {
                        statusCodesText = Object.entries(data.status_codes)
                            .map(([code, count]) => `${code}: ${count}`)
                            .join(', ');
                    }
                    
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${escapeHtml(data.endpoint)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.total_requests || 0}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">${data.successful_requests || 0}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">${data.failed_requests || 0}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${data.rate_limited_requests > 0 ? 'text-orange-600 font-semibold' : 'text-gray-500'}">${data.rate_limited_requests || 0}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.requests_per_second_actual ? data.requests_per_second_actual.toFixed(2) : 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.average_response_time_ms || 0}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.min_response_time_ms || 0}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.max_response_time_ms || 0}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.p90_ms || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${data.p99_ms || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm ${data.error_rate > 5 ? 'text-red-600 font-semibold' : 'text-gray-500'}" title="${statusCodesText}">${data.error_rate || 0}%</td>
                    `;
                    
                    resultsTableBody.appendChild(row);
                } else {
                    const row = document.createElement('tr');
                    row.className = 'bg-red-50';
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${escapeHtml(item.endpoint)}</td>
                        <td colspan="10" class="px-6 py-4 text-sm text-red-600">Error: ${escapeHtml(item.error)}</td>
                    `;
                    resultsTableBody.appendChild(row);
                }
            });
            
            resultsSection.classList.remove('hidden');
        } else {
            // Show error
            document.getElementById('errorText').textContent = result.message || 'An error occurred';
            errorMessage.classList.remove('hidden');
        }
    } catch (error) {
        document.getElementById('errorText').textContent = 'Network error: ' + error.message;
        errorMessage.classList.remove('hidden');
    } finally {
        runTestBtn.disabled = false;
        runTestSpinner.classList.add('hidden');
    }
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endsection
