<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Facades\Log;

class StressTestService
{
    private string $baseUrl;
    private Client $httpClient;

    public function __construct()
    {
        // If running in Docker container, we need to connect to the web server
        // Since we're in the same container, use localhost:80 (Nginx/PHP-FPM) or localhost:80 (Octane)
        // The .dockerenv file exists in all Docker containers
        if (file_exists('/.dockerenv')) {
            // When running inside the app container, use localhost to connect to the web server
            $this->baseUrl = 'http://localhost:80';
            Log::info('StressTestService: Using Docker localhost URL', ['url' => $this->baseUrl]);
        } else {
            // When not in Docker, use the configured app URL or localhost
            $this->baseUrl = config('app.url', 'http://localhost:8000');
            Log::info('StressTestService: Using external URL', ['url' => $this->baseUrl]);
        }
        
        // Create HTTP client with connection pooling for better performance
        $this->httpClient = new Client([
            'timeout' => 30, // Increased timeout for stress tests
            'connect_timeout' => 10,
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

        // Test connection first with a single request
        try {
            $testResponse = $this->httpClient->get($this->baseUrl . '/');
            if ($testResponse->getStatusCode() >= 400) {
                Log::warning('Connection test failed, but continuing with stress test', [
                    'url' => $this->baseUrl,
                    'status' => $testResponse->getStatusCode(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Cannot connect to application', [
                'base_url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Cannot connect to application at {$this->baseUrl}. Make sure the application is running.");
        }

        // Calculate total requests
        $totalRequests = $rps * $duration;
        
        // Use concurrent requests for better performance
        // Calculate how many concurrent requests to maintain based on RPS
        // For high RPS, we'll use a pool of concurrent requests
        // Concurrency should be based on expected response time and RPS
        // If response time is ~10ms and we want 500 RPS, we need ~5 concurrent connections
        // But we'll use a higher concurrency to account for variability
        $concurrency = min(max($rps / 10, 10), 200); // Scale with RPS, min 10, max 200
        
        // Track response times and status codes
        $responseTimes = [];
        $statusCodeCounts = [];
        $successCount = 0;
        $errorCount = 0;
        $rateLimitedCount = 0;
        
        $startTime = microtime(true);
        $endTime = $startTime + $duration;
        $requestCount = 0;
        
        // Calculate how many requests to launch per second to maintain RPS
        $requestsPerSecond = $rps;
        $requestInterval = 1.0 / $requestsPerSecond; // Time between individual requests
        
        Log::info('Starting stress test', [
            'endpoint' => $endpoint,
            'url' => $url,
            'rps' => $rps,
            'duration' => $duration,
            'total_requests' => $totalRequests,
            'concurrency' => $concurrency,
        ]);
        
        // Make concurrent requests at the specified rate
        $promises = [];
        $nextRequestTime = $startTime;
        $lastCleanup = $startTime;
        
        while (microtime(true) < $endTime && $requestCount < $totalRequests) {
            $currentTime = microtime(true);
            
            // Launch new requests to maintain RPS and concurrency
            while ($currentTime >= $nextRequestTime 
                   && count($promises) < $concurrency 
                   && $requestCount < $totalRequests
                   && $currentTime < $endTime) {
                $requestStart = microtime(true);
                
                $promise = $this->httpClient->getAsync($url)
                    ->then(
                        function ($response) use (&$responseTimes, &$statusCodeCounts, &$successCount, &$errorCount, &$rateLimitedCount, $requestStart) {
                            $requestEnd = microtime(true);
                            $responseTime = ($requestEnd - $requestStart) * 1000;
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
                        },
                        function ($exception) use (&$responseTimes, &$statusCodeCounts, &$errorCount, $requestStart) {
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
                    );
                
                $promises[] = $promise;
                $requestCount++;
                $nextRequestTime += $requestInterval;
            }
            
            // Periodically clean up completed promises (every 500ms)
            if ($currentTime - $lastCleanup >= 0.5) {
                // Filter out completed promises by checking their state
                $promises = array_values(array_filter($promises, function ($promise) {
                    try {
                        $state = $promise->getState();
                        return $state === 'pending';
                    } catch (\Exception $e) {
                        return false; // Remove if we can't check state
                    }
                }));
                $lastCleanup = $currentTime;
            }
            
            // Small sleep to prevent CPU spinning when we can't launch more requests
            if (count($promises) >= $concurrency || ($currentTime < $nextRequestTime && count($promises) > 0)) {
                usleep(1000); // 1ms sleep
            }
        }
        
        // Wait for any remaining promises to complete
        if (count($promises) > 0) {
            Promise\Utils::settle($promises)->wait();
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
