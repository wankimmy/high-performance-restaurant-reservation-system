@extends('layouts.admin')

@section('title', 'Monitoring')
@section('page-title', 'System Monitoring Dashboard')

@section('content')
<div id="errorMessage" class="hidden mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>

<div id="dashboardContent">
    <div class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        <p class="mt-4 text-gray-500">Loading metrics...</p>
    </div>
</div>

<div class="mt-4 text-center text-sm text-gray-500">
    Auto-refreshing every minute | Last updated: <span id="lastUpdate">Loading...</span>
</div>
@endsection

@push('scripts')
<script>
    let refreshInterval;
    const REFRESH_INTERVAL = 60000; // 1 minute

    function updateDashboard() {
        fetch('/admin/monitoring/metrics', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderDashboard(data.data);
                document.getElementById('errorMessage').classList.add('hidden');
                updateLastUpdateTime();
            } else {
                showError(data.message || 'Failed to load metrics');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error connecting to server. Please check your connection.');
        });
    }

    function renderDashboard(metrics) {
        const content = document.getElementById('dashboardContent');
        
        content.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- System Resources -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">🖥️ System Resources</h3>
                    
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">CPU Usage</span>
                            <span class="text-sm font-medium text-gray-900">${metrics.system.cpu.usage.toFixed(1)}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-${getStatusColor(metrics.system.cpu.status)} h-2 rounded-full" 
                                 style="width: ${metrics.system.cpu.usage}%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">Memory Usage</span>
                            <span class="text-sm font-medium text-gray-900">${metrics.system.memory.usage.toFixed(1)}%</span>
                        </div>
                        <div class="text-xs text-gray-500 mb-1">${metrics.system.memory.used} / ${metrics.system.memory.total}</div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-${getStatusColor(metrics.system.memory.status)} h-2 rounded-full" 
                                 style="width: ${metrics.system.memory.usage}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">Disk Usage</span>
                            <span class="text-sm font-medium text-gray-900">${metrics.system.disk.usage.toFixed(1)}%</span>
                        </div>
                        <div class="text-xs text-gray-500 mb-1">${metrics.system.disk.used} / ${metrics.system.disk.total}</div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-${getStatusColor(metrics.system.disk.status)} h-2 rounded-full" 
                                 style="width: ${metrics.system.disk.usage}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Queue System -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">📋 Queue System</h3>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-${getStatusColor(metrics.queue.status)}-100 text-${getStatusColor(metrics.queue.status)}-800">
                            ${metrics.queue.status === 'success' ? 'Healthy' : metrics.queue.status === 'warning' ? 'Warning' : 'Critical'}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-3 bg-blue-50 rounded">
                            <div class="text-2xl font-bold text-blue-600">${metrics.queue.pending}</div>
                            <div class="text-xs text-gray-600 mt-1">Pending Jobs</div>
                        </div>
                        <div class="text-center p-3 bg-red-50 rounded">
                            <div class="text-2xl font-bold text-red-600">${metrics.queue.failed}</div>
                            <div class="text-xs text-gray-600 mt-1">Failed Jobs</div>
                        </div>
                    </div>
                </div>

                <!-- Visitors -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">👥 Visitors</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-3 bg-green-50 rounded">
                            <div class="text-2xl font-bold text-green-600">${metrics.visitors.current}</div>
                            <div class="text-xs text-gray-600 mt-1">Active Now</div>
                        </div>
                        <div class="text-center p-3 bg-purple-50 rounded">
                            <div class="text-2xl font-bold text-purple-600">${metrics.visitors.today}</div>
                            <div class="text-xs text-gray-600 mt-1">Today</div>
                        </div>
                    </div>
                </div>

                <!-- Database -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">🗄️ Database</h3>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-${getStatusColor(metrics.database.status)}-100 text-${getStatusColor(metrics.database.status)}-800">
                            ${metrics.database.status === 'success' ? 'OK' : metrics.database.status === 'warning' ? 'Warning' : 'Error'}
                        </span>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Connections</span>
                            <span class="text-sm font-medium text-gray-900">${metrics.database.connections}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Total Queries</span>
                            <span class="text-sm font-medium text-gray-900">${formatNumber(metrics.database.total_queries)}</span>
                        </div>
                    </div>
                </div>

                <!-- Redis -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">💾 Redis</h3>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-${getStatusColor(metrics.redis.status)}-100 text-${getStatusColor(metrics.redis.status)}-800">
                            ${metrics.redis.status === 'success' ? 'OK' : metrics.redis.status === 'warning' ? 'Warning' : 'Error'}
                        </span>
                    </div>
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">Memory Usage</span>
                            <span class="text-sm font-medium text-gray-900">${metrics.redis.percentage.toFixed(1)}%</span>
                        </div>
                        <div class="text-xs text-gray-500 mb-1">${metrics.redis.used_memory} / ${metrics.redis.max_memory}</div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-${getStatusColor(metrics.redis.status)} h-2 rounded-full" 
                                 style="width: ${metrics.redis.percentage}%"></div>
                        </div>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Connected Clients</span>
                        <span class="text-sm font-medium text-gray-900">${metrics.redis.connected_clients}</span>
                    </div>
                </div>
            </div>
        `;
    }

    function getStatusColor(status) {
        return status === 'success' ? 'green' : status === 'warning' ? 'yellow' : 'red';
    }

    function showError(message) {
        const errorDiv = document.getElementById('errorMessage');
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
    }

    function updateLastUpdateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        document.getElementById('lastUpdate').textContent = timeString;
    }

    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    // Initial load
    updateDashboard();

    // Set up auto-refresh
    refreshInterval = setInterval(updateDashboard, REFRESH_INTERVAL);

    // Clean up on page unload
    window.addEventListener('beforeunload', () => {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
</script>
@endpush
