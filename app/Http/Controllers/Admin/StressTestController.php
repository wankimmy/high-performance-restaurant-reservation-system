<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StressTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StressTestController extends Controller
{
    public function __construct(
        private StressTestService $stressTestService
    ) {}

    public function index()
    {
        $serverType = env('SERVER_TYPE', 'nginx');
        
        return view('admin.stress-test.index', [
            'serverType' => $serverType,
        ]);
    }

    public function runTest(Request $request): JsonResponse
    {
        // Increase execution time limit for stress tests
        set_time_limit(600); // 10 minutes
        
        $validator = Validator::make($request->all(), [
            'requests_per_second' => 'required|integer|min:1|max:1000',
            'duration' => 'required|integer|min:5|max:300',
            'endpoints' => 'required|array|min:1',
            'endpoints.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $results = $this->stressTestService->runTests(
                requestsPerSecond: $request->requests_per_second,
                duration: $request->duration,
                endpoints: $request->endpoints
            );

            return response()->json([
                'success' => true,
                'results' => $results,
                'server_type' => env('SERVER_TYPE', 'nginx'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run stress test: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getServerType(): JsonResponse
    {
        return response()->json([
            'server_type' => env('SERVER_TYPE', 'nginx'),
        ]);
    }
}
