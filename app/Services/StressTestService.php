<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class StressTestService
{
    private string $baseUrl;
    private Client $httpClient;

    public function __construct()
    {
        // If running in Docker container, use localhost:80
        if (file_exists('/.dockerenv')) {
            $this->baseUrl = 'http://localhost:80';
        } else {
            $this->baseUrl = config('app.url', 'http://localhost:8000');
        }
        
        // Create HTTP client
        $this->httpClient = new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
            'http_errors' => false,
            'verify' => false,
            'allow_redirects' => true,
            'headers' => [
                'X-Stress-Test' => 'true',
            ],
        ]);
    }

    public function runTests(int $requestsPerSecond, int $duration, array $endpoints): array
    {
        $results = [];
        
        foreach ($endpoints as $endpoint) {
            try {
                $result = $this->testEndpoint($endpoint, $requestsPerSecond, $duration);
                $results[] = [
                    'endpoint' => $endpoint,
                    'success' => true,
                    'data' => $result,
                ];
            } catch (\Exception $e) {
                Log::error('Stress test failed', [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                ]);
                
                $results[] = [
                    'endpoint' => $endpoint,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    private function testEndpoint(string $endpoint, int $rps, int $duration): array
    {
        // Build the full URL
        $url = $this->baseUrl . $endpoint;
        
        // Add query parameters for API endpoints if needed
        if (str_contains($endpoint, '/api/v1/availability')) {
            $date = date('Y-m-d', strtotime('+1 day'));
            $url .= "?date={$date}&time=19:00&pax=4";
        }

        // Calculate total requests and interval
        $totalRequests = $rps * $duration;
        $intervalMicroseconds = (1 / $rps) * 1000000; // Time between requests in microseconds
        
        // Track metrics
        $responseTimes = [];
        $statusCodeCounts = [];
        $successCount = 0;
        $errorCount = 0;
        $rateLimitedCount = 0;
        
        $startTime = microtime(true);
        $endTime = $startTime + $duration;
        $requestCount = 0;
        $nextRequestTime = $startTime;
        
        Log::info('Starting stress test', [
            'endpoint' => $endpoint,
            'url' => $url,
            'rps' => $rps,
            'duration' => $duration,
            'total_requests' => $totalRequests,
        ]);
        
        // Make requests at the specified rate
        while (microtime(true) < $endTime && $requestCount < $totalRequests) {
            $currentTime = microtime(true);
            
            // Launch request when it's time
            if ($currentTime >= $nextRequestTime) {
                $requestStart = microtime(true);
                
                try {
                    $response = $this->httpClient->get($url);
                    $requestEnd = microtime(true);
                    
                    $responseTime = ($requestEnd - $requestStart) * 1000; // Convert to milliseconds
                    $responseTimes[] = $responseTime;
                    
                    $statusCode = $response->getStatusCode();
                    
                    if (!isset($statusCodeCounts[$statusCode])) {
                        $statusCodeCounts[$statusCode] = 0;
                    }
                    $statusCodeCounts[$statusCode]++;
                    
                    if ($statusCode >= 200 && $statusCode < 400) {
                        $successCount++;
                    } elseif ($statusCode === 429) {
                        $rateLimitedCount++;
                        $errorCount++;
                    } else {
                        $errorCount++;
                    }
                } catch (\Exception $e) {
                    $requestEnd = microtime(true);
                    $responseTime = ($requestEnd - $requestStart) * 1000;
                    $responseTimes[] = $responseTime;
                    
                    $statusCode = 0;
                    if (!isset($statusCodeCounts[$statusCode])) {
                        $statusCodeCounts[$statusCode] = 0;
                    }
                    $statusCodeCounts[$statusCode]++;
                    $errorCount++;
                }
                
                $requestCount++;
                $nextRequestTime += ($intervalMicroseconds / 1000000); // Convert back to seconds
            } else {
                // Sleep until next request time
                $sleepTime = ($nextRequestTime - $currentTime) * 1000000; // Convert to microseconds
                if ($sleepTime > 0) {
                    usleep((int) $sleepTime);
                }
            }
        }
        
        $actualDuration = microtime(true) - $startTime;
        
        // Calculate statistics
        sort($responseTimes);
        $count = count($responseTimes);
        
        $result = [
            'endpoint' => $endpoint,
            'requests_per_second' => $rps,
            'duration' => $duration,
            'total_requests' => $requestCount,
            'successful_requests' => $successCount,
            'failed_requests' => $errorCount,
            'rate_limited_requests' => $rateLimitedCount,
            'requests_per_second_actual' => $count > 0 ? round($count / $actualDuration, 2) : 0,
            'average_response_time_ms' => $count > 0 ? round(array_sum($responseTimes) / $count, 2) : 0,
            'min_response_time_ms' => $count > 0 ? round(min($responseTimes), 2) : 0,
            'max_response_time_ms' => $count > 0 ? round(max($responseTimes), 2) : 0,
            'p50_ms' => $count > 0 ? round($this->percentile($responseTimes, 50), 2) : 0,
            'p75_ms' => $count > 0 ? round($this->percentile($responseTimes, 75), 2) : 0,
            'p90_ms' => $count > 0 ? round($this->percentile($responseTimes, 90), 2) : 0,
            'p99_ms' => $count > 0 ? round($this->percentile($responseTimes, 99), 2) : 0,
            'error_rate' => $requestCount > 0 ? round(($errorCount / $requestCount) * 100, 2) : 0,
            'status_codes' => $statusCodeCounts,
        ];
        
        Log::info('Stress test completed', [
            'endpoint' => $endpoint,
            'result' => $result,
        ]);
        
        return $result;
    }

    /**
     * Calculate percentile from sorted array
     */
    private function percentile(array $sortedArray, float $percentile): float
    {
        $count = count($sortedArray);
        if ($count === 0) {
            return 0;
        }
        
        $index = ($percentile / 100) * ($count - 1);
        $lower = floor($index);
        $upper = ceil($index);
        
        if ($lower === $upper) {
            return $sortedArray[(int) $index];
        }
        
        $weight = $index - $lower;
        return $sortedArray[(int) $lower] * (1 - $weight) + $sortedArray[(int) $upper] * $weight;
    }
}
