<?php

declare(strict_types=1);

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\FoundItemApiController;
use App\Http\Controllers\Api\LostReportApiController;
use App\Http\Controllers\Api\MatchApiController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:60,1'])->group(function (): void {
    // Dashboard
    Route::prefix('dashboard')->group(function (): void {
        Route::get('stats', [DashboardApiController::class, 'stats']);
        Route::get('weekly-trend', [DashboardApiController::class, 'weeklyTrend']);
    });

    // Found Items
    Route::apiResource('found-items', FoundItemApiController::class);
    Route::patch('found-items/{id}/status', [FoundItemApiController::class, 'updateStatus']);
    Route::get('found-items/export/police', [FoundItemApiController::class, 'exportPolice']);

    // Lost Reports
    Route::apiResource('lost-reports', LostReportApiController::class)->except(['destroy']);
    Route::patch('lost-reports/{id}/status', [LostReportApiController::class, 'updateStatus']);

    // Matches
    Route::get('matches', [MatchApiController::class, 'index']);

    // Upload
    Route::post('upload/image', [UploadController::class, 'image']);
    Route::post('analyze-image', [UploadController::class, 'analyze']);
});
