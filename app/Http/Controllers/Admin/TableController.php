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
        $query = Table::withCount('reservations')
            ->with(['reservations' => function ($q) {
                $q->where('status', 'confirmed')
                  ->where(function ($query) {
                      $query->where('reservation_date', '>', now()->format('Y-m-d'))
                            ->orWhere(function ($q) {
                                $q->where('reservation_date', now()->format('Y-m-d'))
                                  ->where('reservation_time', '>=', now()->format('H:i'));
                            });
                  })
                  ->orderBy('reservation_date')
                  ->orderBy('reservation_time')
                  ->limit(1);
            }])
            ->orderBy('name');

        // Filter by availability
        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        // Filter by capacity
        if ($request->has('min_capacity')) {
            $query->where('capacity', '>=', $request->min_capacity);
        }
        if ($request->has('max_capacity')) {
            $query->where('capacity', '<=', $request->max_capacity);
        }

        $tables = $query->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($tables);
        }

        return view('admin.tables.index', compact('tables'));
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
     * Toggle table availability.
     */
    public function toggleAvailability(Request $request, Table $table): JsonResponse
    {
        $table->is_available = !$table->is_available;
        $table->save();

        return response()->json([
            'success' => true,
            'message' => 'Table availability updated',
            'table' => $table,
        ]);
    }
}
