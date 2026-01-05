<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;

class MonitoringController extends Controller
{
    public function index()
    {
        return view('admin.monitoring.dashboard');
    }

    public function getMetrics(): JsonResponse
    {
        try {
            $metrics = [
                'system' => $this->getSystemMetrics(),
                'queue' => $this->getQueueMetrics(),
                'workers' => $this->getWorkerMetrics(),
                'visitors' => $this->getVisitorMetrics(),
                'database' => $this->getDatabaseMetrics(),
                'redis' => $this->getRedisMetrics(),
                'timestamp' => now()->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching metrics: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getSystemMetrics(): array
    {
        $cpuUsage = $this->getCpuUsage();
        $memoryUsage = $this->getMemoryUsage();
        $diskUsage = $this->getDiskUsage();

        return [
            'cpu' => [
                'usage' => $cpuUsage,
                'status' => $this->getStatusColor($cpuUsage, 80, 90),
            ],
            'memory' => [
                'usage' => $memoryUsage['percentage'],
                'used' => $memoryUsage['used'],
                'total' => $memoryUsage['total'],
                'status' => $this->getStatusColor($memoryUsage['percentage'], 80, 90),
            ],
            'disk' => [
                'usage' => $diskUsage['percentage'],
                'used' => $diskUsage['used'],
                'total' => $diskUsage['total'],
                'status' => $this->getStatusColor($diskUsage['percentage'], 80, 90),
            ],
        ];
    }

    private function getCpuUsage(): float
    {
        // Try to get from cache first (updated every 30 seconds)
        $cached = Cache::get('cpu_usage', null);
        if ($cached !== null) {
            return $cached;
        }
        
        $cpuUsage = 0;
        
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows CPU usage
            try {
                $output = @shell_exec('wmic cpu get loadpercentage /value 2>&1');
                if ($output && preg_match('/LoadPercentage=(\d+)/', $output, $matches)) {
                    $cpuUsage = (float) $matches[1];
                } else {
                    // Fallback: use system load if available
                    $load = @sys_getloadavg();
                    $cpuUsage = $load ? min(100, ($load[0] * 100)) : 0;
                }
            } catch (\Exception $e) {
                $cpuUsage = 0;
            }
        } else {
            // Linux/Unix CPU usage - use cached /proc/stat data
            try {
                $statFile = '/proc/stat';
                if (file_exists($statFile)) {
                    $stat1 = file_get_contents($statFile);
                    $lastStat = Cache::get('cpu_stat_last', null);
                    $lastTime = Cache::get('cpu_stat_time', null);
                    
                    if ($lastStat && $lastTime && (time() - $lastTime) >= 1) {
                        $info1 = preg_split("/\s+/", substr($lastStat, 0, strpos($lastStat, "\n")));
                        $info2 = preg_split("/\s+/", substr($stat1, 0, strpos($stat1, "\n")));
                        
                        if (count($info1) >= 5 && count($info2) >= 5) {
                            $dif = [];
                            $dif['user'] = $info2[1] - $info1[1];
                            $dif['nice'] = $info2[2] - $info1[2];
                            $dif['sys'] = $info2[3] - $info1[3];
                            $dif['idle'] = $info2[4] - $info1[4];
                            $total = array_sum($dif);
                            
                            if ($total > 0) {
                                $cpuUsage = (($total - $dif['idle']) / $total) * 100;
                            }
                        }
                    }
                    
                    // Cache current stat for next calculation
                    Cache::put('cpu_stat_last', $stat1, now()->addMinutes(2));
                    Cache::put('cpu_stat_time', time(), now()->addMinutes(2));
                } else {
                    // Fallback to load average
                    $load = @sys_getloadavg();
                    if ($load) {
                        $cpuCount = (int) @shell_exec('nproc 2>/dev/null');
                        $cpuUsage = min(100, ($load[0] / max($cpuCount, 1)) * 100);
                    }
                }
            } catch (\Exception $e) {
                $cpuUsage = 0;
            }
        }
        
        // Cache the result for 30 seconds
        Cache::put('cpu_usage', round($cpuUsage, 2), now()->addSeconds(30));
        
        return round($cpuUsage, 2);
    }

    private function getMemoryUsage(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /value 2>&1');
            preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $totalMatches);
            preg_match('/FreePhysicalMemory=(\d+)/', $output, $freeMatches);
            
            if (isset($totalMatches[1]) && isset($freeMatches[1])) {
                $total = (int) $totalMatches[1] * 1024; // Convert from KB to bytes
                $free = (int) $freeMatches[1] * 1024;
                $used = $total - $free;
                $percentage = ($used / $total) * 100;
                
                return [
                    'used' => $this->formatBytes($used),
                    'total' => $this->formatBytes($total),
                    'percentage' => round($percentage, 2),
                ];
            }
        } else {
            $meminfo = file_get_contents('/proc/meminfo');
            if ($meminfo) {
                preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $totalMatches);
                preg_match('/MemAvailable:\s+(\d+)\s+kB/', $meminfo, $availMatches);
                
                if (isset($totalMatches[1]) && isset($availMatches[1])) {
                    $total = (int) $totalMatches[1] * 1024;
                    $available = (int) $availMatches[1] * 1024;
                    $used = $total - $available;
                    $percentage = ($used / $total) * 100;
                    
                    return [
                        'used' => $this->formatBytes($used),
                        'total' => $this->formatBytes($total),
                        'percentage' => round($percentage, 2),
                    ];
                }
            }
        }
        
        // Fallback
        $memoryLimit = ini_get('memory_limit');
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        return [
            'used' => $this->formatBytes($memoryPeak),
            'total' => $memoryLimit,
            'percentage' => 0,
        ];
    }

    private function getDiskUsage(): array
    {
        $path = base_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        $percentage = ($used / $total) * 100;
        
        return [
            'used' => $this->formatBytes($used),
            'total' => $this->formatBytes($total),
            'percentage' => round($percentage, 2),
        ];
    }

    private function getQueueMetrics(): array
    {
        try {
            $connection = config('queue.connections.redis.connection', 'default');
            $redis = Redis::connection($connection);
            
            $queueName = config('queue.connections.redis.queue', 'default');
            $reservationsQueue = 'queues:reservations';
            
            // Get queue sizes
            $pending = $redis->llen($reservationsQueue) ?? 0;
            $failed = DB::table('failed_jobs')->count();
            
            // Get processing stats from cache
            $processedToday = Cache::get('queue_processed_today', 0);
            $failedToday = Cache::get('queue_failed_today', 0);
            
            return [
                'pending' => $pending,
                'failed' => $failed,
                'processed_today' => $processedToday,
                'failed_today' => $failedToday,
                'status' => $pending > 100 ? 'warning' : ($pending > 500 ? 'danger' : 'success'),
            ];
        } catch (\Exception $e) {
            return [
                'pending' => 0,
                'failed' => 0,
                'processed_today' => 0,
                'failed_today' => 0,
                'status' => 'danger',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getWorkerMetrics(): array
    {
        try {
            $connection = config('queue.connections.redis.connection', 'default');
            $redis = Redis::connection($connection);
            
            // Check for active workers (Laravel stores worker info in Redis)
            $workers = [];
            $workerKeys = $redis->keys('laravel_database_*:workers:*');
            
            $activeWorkers = 0;
            $totalWorkers = 0;
            
            foreach ($workerKeys as $key) {
                $workerData = $redis->get($key);
                if ($workerData) {
                    $totalWorkers++;
                    $data = json_decode($workerData, true);
                    if (isset($data['status']) && $data['status'] === 'running') {
                        $activeWorkers++;
                    }
                }
            }
            
            // Alternative: Check process list
            if ($totalWorkers === 0) {
                $processCheck = $this->checkWorkerProcesses();
                $activeWorkers = $processCheck['active'];
                $totalWorkers = $processCheck['total'];
            }
            
            return [
                'active' => $activeWorkers,
                'total' => max($totalWorkers, 1),
                'status' => $activeWorkers > 0 ? 'success' : 'danger',
                'is_running' => $activeWorkers > 0,
            ];
        } catch (\Exception $e) {
            return [
                'active' => 0,
                'total' => 0,
                'status' => 'danger',
                'is_running' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkWorkerProcesses(): array
    {
        $command = "ps aux | grep 'queue:work' | grep -v grep";
        $output = shell_exec($command);
        
        if (empty($output)) {
            return ['active' => 0, 'total' => 0];
        }
        
        $lines = explode("\n", trim($output));
        $active = 0;
        
        foreach ($lines as $line) {
            if (strpos($line, 'queue:work') !== false) {
                $active++;
            }
        }
        
        return ['active' => $active, 'total' => $active];
    }

    private function getVisitorMetrics(): array
    {
        $now = now();
        
        try {
            // Try Redis sets first (more efficient)
            $redis = Redis::connection();
            
            $activeKey = 'active_visitors_set';
            $currentVisitors = $redis->exists($activeKey) ? $redis->scard($activeKey) : Cache::get('current_visitors', 0);
            
            $lastMinuteKey = 'visitors_minute_' . $now->format('Y-m-d-H-i');
            $visitorsLastMinute = $redis->exists($lastMinuteKey) ? $redis->scard($lastMinuteKey) : Cache::get('visitors_last_minute', 0);
            
            $lastHourKey = 'visitors_hour_' . $now->format('Y-m-d-H');
            $visitorsLastHour = $redis->exists($lastHourKey) ? $redis->scard($lastHourKey) : Cache::get('visitors_last_hour', 0);
            
            $todayKey = 'visitors_today_' . $now->format('Y-m-d');
            $visitorsToday = $redis->exists($todayKey) ? $redis->scard($todayKey) : Cache::get('visitors_today', 0);
            
            $recentKey = 'recent_visitor_ips_' . $now->format('Y-m-d-H-i');
            $uniqueVisitors = $redis->exists($recentKey) ? $redis->scard($recentKey) : 0;
        } catch (\Exception $e) {
            // Fallback to cache
            $currentVisitors = Cache::get('current_visitors', 0);
            $visitorsLastMinute = Cache::get('visitors_last_minute', 0);
            $visitorsLastHour = Cache::get('visitors_last_hour', 0);
            $visitorsToday = Cache::get('visitors_today', 0);
            $recentVisitors = Cache::get('recent_visitor_ips', []);
            $uniqueVisitors = count(array_unique($recentVisitors));
        }
        
        return [
            'current' => (int) $currentVisitors,
            'last_minute' => (int) $visitorsLastMinute,
            'last_hour' => (int) $visitorsLastHour,
            'today' => (int) $visitorsToday,
            'unique_last_minute' => (int) $uniqueVisitors,
        ];
    }

    private function getDatabaseMetrics(): array
    {
        try {
            $connections = DB::select('SHOW STATUS LIKE "Threads_connected"');
            $threadsConnected = $connections[0]->Value ?? 0;
            
            $queries = DB::select('SHOW STATUS LIKE "Questions"');
            $totalQueries = $queries[0]->Value ?? 0;
            
            return [
                'connections' => (int) $threadsConnected,
                'total_queries' => (int) $totalQueries,
                'status' => $threadsConnected > 50 ? 'warning' : 'success',
            ];
        } catch (\Exception $e) {
            return [
                'connections' => 0,
                'total_queries' => 0,
                'status' => 'danger',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getRedisMetrics(): array
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info('memory');
            
            $usedMemory = $info['used_memory'] ?? 0;
            $usedMemoryHuman = $info['used_memory_human'] ?? '0B';
            $maxMemory = $info['maxmemory'] ?? 0;
            $maxMemoryHuman = $info['maxmemory_human'] ?? '0B';
            
            $percentage = $maxMemory > 0 ? ($usedMemory / $maxMemory) * 100 : 0;
            
            $connectedClients = $redis->info('clients')['connected_clients'] ?? 0;
            
            return [
                'used_memory' => $this->formatBytes($usedMemory),
                'used_memory_raw' => $usedMemory,
                'max_memory' => $maxMemoryHuman,
                'max_memory_raw' => $maxMemory,
                'percentage' => round($percentage, 2),
                'connected_clients' => (int) $connectedClients,
                'status' => $this->getStatusColor($percentage, 80, 90),
            ];
        } catch (\Exception $e) {
            return [
                'used_memory' => '0B',
                'max_memory' => '0B',
                'percentage' => 0,
                'connected_clients' => 0,
                'status' => 'danger',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getStatusColor(float $value, float $warning, float $danger): string
    {
        if ($value >= $danger) {
            return 'danger';
        } elseif ($value >= $warning) {
            return 'warning';
        }
        return 'success';
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

