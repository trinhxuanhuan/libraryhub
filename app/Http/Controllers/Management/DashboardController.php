<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatisticsService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(
        DashboardStatisticsService $dashboardStatistics
    ): View {
        $statistics = $dashboardStatistics->statistics();

        $monthlyBorrowings = $dashboardStatistics->monthlyBorrowings();

        $topBooks = $dashboardStatistics->topBooks();

        return view('management.dashboard.index', compact(
            'statistics',
            'monthlyBorrowings',
            'topBooks'
        ));
    }
}
