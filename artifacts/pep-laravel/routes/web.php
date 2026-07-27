<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FoundItemController;
use App\Http\Controllers\LostReportController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Found Items
Route::resource('found-items', FoundItemController::class);
Route::patch('found-items/{id}/status', [FoundItemController::class, 'updateStatus'])->name('found-items.update-status');

// Lost Reports
Route::resource('lost-reports', LostReportController::class)->except(['destroy']);
Route::patch('lost-reports/{id}/status', [LostReportController::class, 'updateStatus'])->name('lost-reports.update-status');

// Matches
Route::get('matches', [MatchController::class, 'index'])->name('matches.index');

// Export
Route::get('export/police', [ExportController::class, 'policeForm'])->name('export.police-form');
Route::get('export/police/download', [ExportController::class, 'policeCsv'])->name('export.police-csv');

// Upload
Route::post('upload/image', [UploadController::class, 'image'])->name('upload.image');
Route::post('analyze-image', [UploadController::class, 'analyze'])->name('analyze.image');
