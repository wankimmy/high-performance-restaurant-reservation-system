<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Table;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->format('Y-m-d');
        
        $todayReservationsCount = Reservation::whereDate('reservation_date', today())->count();
        $totalReservationsCount = Reservation::count();
        $totalTablesCount = Table::count();
        
        // Calculate available tables
        $allTables = Table::all();
        $availableCount = 0;
        
        foreach ($allTables as $table) {
            $hasBookingToday = Reservation::where('table_id', $table->id)
                ->where('status', 'confirmed')
                ->where('reservation_date', $today)
                ->exists();
            
            if ($table->is_available && !$hasBookingToday) {
                $availableCount++;
            }
        }
        
        $recentReservations = Reservation::with('table')
            ->latest()
            ->take(5)
            ->get();
        
        return view('admin.dashboard', [
            'todayReservationsCount' => $todayReservationsCount,
            'totalReservationsCount' => $totalReservationsCount,
            'totalTablesCount' => $totalTablesCount,
            'availableTablesCount' => $availableCount,
            'recentReservations' => $recentReservations,
        ]);
    }
}
