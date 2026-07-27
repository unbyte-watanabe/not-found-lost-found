<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\FoundItemRepository;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ExportController extends Controller
{
    public function __construct(
        private readonly FoundItemRepository $repository,
        private readonly ExportService       $exportService,
    ) {
    }

    public function policeForm(): View
    {
        return view('exports.police-form');
    }

    public function policeCsv(Request $request): Response
    {
        $validated = $request->validate([
            'dateFrom' => ['nullable', 'date'],
            'dateTo'   => ['nullable', 'date', 'after_or_equal:dateFrom'],
            'status'   => ['nullable', 'string', 'in:保管中,返還済,警察提出済,期間満了処分'],
        ]);

        $items = $this->repository->getForExport($validated);

        $csv      = $this->exportService->generatePoliceCsv($items);
        $filename = '落とし物_警察提出用_' . now()->format('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
