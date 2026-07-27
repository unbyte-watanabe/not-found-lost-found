<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(): View
    {
        $stats       = $this->dashboardService->getStats();
        $weeklyTrend = $this->dashboardService->getWeeklyTrend();

        return view('dashboard.index', compact('stats', 'weeklyTrend'));
    }
}
