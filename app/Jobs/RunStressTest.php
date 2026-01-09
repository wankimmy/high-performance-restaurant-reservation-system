<?php

namespace App\Jobs;

use App\Services\StressTestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunStressTest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600; // 10 minutes

    public function __construct(
        public string $testId,
        public int $requestsPerSecond,
        public int $duration,
        public array $endpoints
    ) {
        $this->onQueue('default');
    }

    public function handle(StressTestService $stressTestService): void
    {
        try {
            // Mark test as running (testId already includes 'stress_test_' prefix)
            Cache::put($this->testId, [
                'status' => 'running',
                'progress' => 0,
                'message' => 'Starting stress test...',
            ], 3600);

            Log::info('Starting stress test job', [
                'test_id' => $this->testId,
                'rps' => $this->requestsPerSecond,
                'duration' => $this->duration,
                'endpoints' => $this->endpoints,
            ]);

            // Run the stress tests
            $results = $stressTestService->runTests(
                requestsPerSecond: $this->requestsPerSecond,
                duration: $this->duration,
                endpoints: $this->endpoints
            );

            // Store results in cache (testId already includes 'stress_test_' prefix)
            Cache::put($this->testId, [
                'status' => 'completed',
                'progress' => 100,
                'results' => $results,
                'server_type' => env('SERVER_TYPE', 'nginx'),
                'completed_at' => now()->toIso8601String(),
            ], 3600);

            Log::info('Stress test job completed', [
                'test_id' => $this->testId,
                'results_count' => count($results),
            ]);
        } catch (\Exception $e) {
            Log::error('Stress test job failed', [
                'test_id' => $this->testId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Store error in cache (testId already includes 'stress_test_' prefix)
            Cache::put($this->testId, [
                'status' => 'failed',
                'progress' => 0,
                'error' => $e->getMessage(),
                'failed_at' => now()->toIso8601String(),
            ], 3600);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Stress test job failed permanently', [
            'test_id' => $this->testId,
            'error' => $exception->getMessage(),
        ]);

        // testId already includes 'stress_test_' prefix
        Cache::put($this->testId, [
            'status' => 'failed',
            'progress' => 0,
            'error' => $exception->getMessage(),
            'failed_at' => now()->toIso8601String(),
        ], 3600);
    }
}
