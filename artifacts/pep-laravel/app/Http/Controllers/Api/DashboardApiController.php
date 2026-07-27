<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardApiController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->dashboardService->getStats());
    }

    public function weeklyTrend(): JsonResponse
    {
        return response()->json($this->dashboardService->getWeeklyTrend());
    }
}
