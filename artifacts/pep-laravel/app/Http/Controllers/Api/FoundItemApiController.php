<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFoundItemRequest;
use App\Http\Requests\UpdateFoundItemRequest;
use App\Repositories\FoundItemRepository;
use App\Services\ExportService;
use App\Services\ManagementNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class FoundItemApiController extends Controller
{
    public function __construct(
        private readonly FoundItemRepository     $repository,
        private readonly ManagementNumberService $managementNumberService,
        private readonly ExportService           $exportService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'category', 'keyword', 'dateFrom', 'dateTo']);
        $items   = $this->repository->paginate($filters, 20);

        return response()->json($items);
    }

    public function store(StoreFoundItemRequest $request): JsonResponse
    {
        $item = DB::transaction(function () use ($request) {
            $managementNo = $this->managementNumberService->generate();

            return $this->repository->create(
                array_merge($request->validated(), [
                    'management_no' => $managementNo,
                    'status'        => '保管中',
                ])
            );
        });

        return response()->json($item, 201);
    }

    public function show(string $id): JsonResponse
    {
        $item = $this->repository->findOrFail($id);

        return response()->json($item);
    }

    public function update(UpdateFoundItemRequest $request, string $id): JsonResponse
    {
        $item = DB::transaction(fn () => $this->repository->update($id, $request->validated()));

        return response()->json($item);
    }

    public function destroy(string $id): JsonResponse
    {
        DB::transaction(fn () => $this->repository->delete($id));

        return response()->json(['success' => true]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:保管中,返還済,警察提出済,期間満了処分'],
        ]);

        $item = DB::transaction(fn () => $this->repository->updateStatus($id, $validated['status']));

        return response()->json($item);
    }

    public function exportPolice(Request $request): Response
    {
        $validated = $request->validate([
            'dateFrom' => ['nullable', 'date'],
            'dateTo'   => ['nullable', 'date', 'after_or_equal:dateFrom'],
            'status'   => ['nullable', 'string', 'in:保管中,返還済,警察提出済,期間満了処分'],
        ]);

        $items    = $this->repository->getForExport($validated);
        $csv      = $this->exportService->generatePoliceCsv($items);
        $filename = '落とし物_警察提出用_' . now()->format('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
