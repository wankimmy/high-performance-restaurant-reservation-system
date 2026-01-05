@extends('layouts.admin')

@section('title', 'System Monitoring Dashboard')

@section('page-title', '📊 System Monitoring Dashboard')

@section('subtitle-class', 'refresh-info')
@section('page-subtitle')
    Auto-refreshing every minute | Last updated: <span id="lastUpdate">Loading...</span>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/monitoring.css') }}">
@endpush

@section('content')
<div id="errorMessage" class="error" style="display: none;"></div>

<div id="dashboardContent">
    <div class="loading">
        <div class="loading-indicator">Loading metrics...</div>
    </div>
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
                document.getElementById('errorMessage').style.display = 'none';
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
            <div class="grid">
                <!-- System Resources -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">🖥️ System Resources</span>
                    </div>
                    <div class="metric">
                        <div class="metric-label">CPU Usage</div>
                        <div class="metric-value">${metrics.system.cpu.usage.toFixed(1)}%</div>
                        <div class="progress-bar">
                            <div class="progress-fill ${metrics.system.cpu.status}" 
                                 style="width: ${metrics.system.cpu.usage}%"></div>
                        </div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Memory Usage</div>
                        <div class="metric-value">${metrics.system.memory.usage.toFixed(1)}%</div>
                        <div class="metric-detail">${metrics.system.memory.used} / ${metrics.system.memory.total}</div>
                        <div class="progress-bar">
                            <div class="progress-fill ${metrics.system.memory.status}" 
                                 style="width: ${metrics.system.memory.usage}%"></div>
                        </div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Disk Usage</div>
                        <div class="metric-value">${metrics.system.disk.usage.toFixed(1)}%</div>
                        <div class="metric-detail">${metrics.system.disk.used} / ${metrics.system.disk.total}</div>
                        <div class="progress-bar">
                            <div class="progress-fill ${metrics.system.disk.status}" 
                                 style="width: ${metrics.system.disk.usage}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Queue System -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">📋 Queue System</span>
                        <span class="status-badge ${metrics.queue.status}">
                            ${metrics.queue.status === 'success' ? 'Healthy' : 
                              metrics.queue.status === 'warning' ? 'Warning' : 'Critical'}
                        </span>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value">${metrics.queue.pending}</div>
                            <div class="stat-label">Pending Jobs</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${metrics.queue.failed}</div>
                            <div class="stat-label">Failed Jobs</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${metrics.queue.processed_today}</div>
                            <div class="stat-label">Processed Today</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${metrics.queue.failed_today}</div>
                            <div class="stat-label">Failed Today</div>
                        </div>
                    </div>
                </div>

                <!-- Workers -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">⚙️ Queue Workers</span>
                        <span class="status-badge ${metrics.workers.status}">
                            ${metrics.workers.is_running ? 'Running' : 'Stopped'}
                        </span>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Active Workers</div>
                        <div class="metric-value">${metrics.workers.active} / ${metrics.workers.total}</div>
                        <div class="metric-detail">
                            ${metrics.workers.is_running ? 
                                '✅ Workers are processing jobs' : 
                                '❌ No workers running. Start queue worker!'}
                        </div>
                    </div>
                </div>

                <!-- Visitors -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">👥 Visitors</span>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value">${metrics.visitors.current}</div>
                            <div class="stat-label">Active Now</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${metrics.visitors.unique_last_minute}</div>
                            <div class="stat-label">Last Minute</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${metrics.visitors.last_hour}</div>
                            <div class="stat-label">Last Hour</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${metrics.visitors.today}</div>
                            <div class="stat-label">Today</div>
                        </div>
                    </div>
                </div>

                <!-- Database -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">🗄️ Database</span>
                        <span class="status-badge ${metrics.database.status}">
                            ${metrics.database.status === 'success' ? 'OK' : 
                              metrics.database.status === 'warning' ? 'Warning' : 'Error'}
                        </span>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value">${metrics.database.connections}</div>
                            <div class="stat-label">Connections</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${formatNumber(metrics.database.total_queries)}</div>
                            <div class="stat-label">Total Queries</div>
                        </div>
                    </div>
                </div>

                <!-- Redis -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">💾 Redis</span>
                        <span class="status-badge ${metrics.redis.status}">
                            ${metrics.redis.status === 'success' ? 'OK' : 
                              metrics.redis.status === 'warning' ? 'Warning' : 'Error'}
                        </span>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Memory Usage</div>
                        <div class="metric-value">${metrics.redis.percentage.toFixed(1)}%</div>
                        <div class="metric-detail">${metrics.redis.used_memory} / ${metrics.redis.max_memory}</div>
                        <div class="progress-bar">
                            <div class="progress-fill ${metrics.redis.status}" 
                                 style="width: ${metrics.redis.percentage}%"></div>
                        </div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Connected Clients</div>
                        <div class="metric-value">${metrics.redis.connected_clients}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function showError(message) {
        const errorDiv = document.getElementById('errorMessage');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
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
