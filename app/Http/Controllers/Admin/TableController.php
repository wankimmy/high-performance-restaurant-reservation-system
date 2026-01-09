<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TableController extends Controller
{
    /**
     * Display a listing of tables.
     */
    public function index(Request $request): View|JsonResponse
    {
        // Get date and time for availability check (default to today and current time)
        $checkDate = $request->get('check_date', now()->format('Y-m-d'));
        $checkTime = $request->get('check_time', now()->format('H:i'));
        $checkDateTime = \Carbon\Carbon::parse($checkDate . ' ' . $checkTime);
        
        $query = Table::with(['reservations' => function ($q) use ($checkDate) {
                // Get all future reservations for this table on the checked date
                $q->where('reservation_date', $checkDate)
                  ->where('status', '!=', 'cancelled')
                  ->orderBy('reservation_time');
            }])
            ->orderBy('name');

        // Filter by capacity
        if ($request->filled('min_capacity')) {
            $query->where('capacity', '>=', $request->min_capacity);
        }
        if ($request->filled('max_capacity')) {
            $query->where('capacity', '<=', $request->max_capacity);
        }

        $tables = $query->get();
        
        // Calculate availability for each table based on reservations
        $tables = $tables->map(function ($table) use ($checkDateTime) {
            $table->is_available_at_check_time = $this->isTableAvailableAt($table, $checkDateTime);
            return $table;
        });
        
        // Filter by availability if requested
        if ($request->filled('availability_status')) {
            $status = $request->get('availability_status');
            if ($status === 'available') {
                $tables = $tables->filter(fn($table) => $table->is_available_at_check_time);
            } elseif ($status === 'unavailable') {
                $tables = $tables->filter(fn($table) => !$table->is_available_at_check_time);
            }
        }

        // Paginate manually since we've modified the collection
        $perPage = 50;
        $currentPage = $request->get('page', 1);
        
        // Preserve all query parameters for pagination links
        $queryParams = $request->except('page');
        $tables = new \Illuminate\Pagination\LengthAwarePaginator(
            $tables->forPage($currentPage, $perPage),
            $tables->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $queryParams
            ]
        );

        if ($request->expectsJson()) {
            return response()->json($tables);
        }

        return view('admin.tables.index', compact('tables', 'checkDate', 'checkTime'));
    }
    
    /**
     * Check if a table is available at a specific date and time based on reservations
     */
    private function isTableAvailableAt(Table $table, \Carbon\Carbon $checkDateTime): bool
    {
        $checkDate = $checkDateTime->format('Y-m-d');
        $checkTime = $checkDateTime->format('H:i');
        $checkEndTime = $checkDateTime->copy()->addMinutes(105); // 1 hour 45 minutes
        
        // Get all reservations for this table on the checked date
        $reservations = $table->reservations()
            ->where('reservation_date', $checkDate)
            ->where('status', '!=', 'cancelled')
            ->get();
        
        // Check if any reservation overlaps with the checked time slot
        foreach ($reservations as $reservation) {
            $reservationDateTime = \Carbon\Carbon::parse(
                $reservation->reservation_date->format('Y-m-d') . ' ' . $reservation->reservation_time
            );
            $reservationEndTime = $reservationDateTime->copy()->addMinutes(105);
            
            // Check for overlap: check start < reservation end AND check end > reservation start
            if ($checkDateTime->lt($reservationEndTime) && $checkEndTime->gt($reservationDateTime)) {
                return false; // Table is not available due to overlap
            }
        }
        
        return true; // Table is available
    }

    /**
     * Show the form for creating a new table.
     */
    public function create(): View
    {
        return view('admin.tables.create');
    }

    /**
     * Store a newly created table.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191|unique:tables,name',
            'capacity' => 'required|integer|min:1|max:20',
            'is_available' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $table = Table::create($validator->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Table created successfully',
                'table' => $table,
            ], 201);
        }

        return redirect()->route('admin.tables.index')
            ->with('success', 'Table created successfully');
    }

    /**
     * Show the form for editing the specified table.
     */
    public function edit(Table $table): View
    {
        return view('admin.tables.edit', compact('table'));
    }

    /**
     * Update the specified table.
     */
    public function update(Request $request, Table $table): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191|unique:tables,name,' . $table->id,
            'capacity' => 'required|integer|min:1|max:20',
            'is_available' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $table->update($validator->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Table updated successfully',
                'table' => $table,
            ]);
        }

        return redirect()->route('admin.tables.index')
            ->with('success', 'Table updated successfully');
    }

    /**
     * Remove the specified table.
     */
    public function destroy(Request $request, Table $table): RedirectResponse|JsonResponse
    {
        // Check if table has active reservations
        $activeReservations = $table->reservations()
            ->where('status', '!=', 'cancelled')
            ->where('reservation_date', '>=', now()->format('Y-m-d'))
            ->count();

        if ($activeReservations > 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete table with active reservations',
                ], 400);
            }
            return back()->with('error', 'Cannot delete table with active reservations');
        }

        $table->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Table deleted successfully',
            ]);
        }

        return redirect()->route('admin.tables.index')
            ->with('success', 'Table deleted successfully');
    }

    /**
     * Toggle table availability (for maintenance purposes only).
     * Note: Booking availability is determined by reservations, not this field.
     */
    public function toggleAvailability(Request $request, Table $table): JsonResponse
    {
        $table->is_available = !$table->is_available;
        $table->save();

        return response()->json([
            'success' => true,
            'message' => 'Table maintenance status updated. Note: Booking availability is based on reservations.',
            'table' => $table,
        ]);
    }
}
