<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLostReportRequest;
use App\Http\Requests\UpdateLostReportRequest;
use App\Repositories\FoundItemRepository;
use App\Repositories\LostReportRepository;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LostReportApiController extends Controller
{
    public function __construct(
        private readonly LostReportRepository $repository,
        private readonly FoundItemRepository  $foundItemRepository,
        private readonly MatchingService      $matchingService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'category', 'keyword']);
        $reports = $this->repository->paginate($filters, 20);

        return response()->json($reports);
    }

    public function store(StoreLostReportRequest $request): JsonResponse
    {
        $report = DB::transaction(function () use ($request) {
            return $this->repository->create(
                array_merge($request->validated(), ['status' => '探索中'])
            );
        });

        // Find matches among 保管中 found items
        $foundItems = $this->foundItemRepository->allByStatus('保管中');
        $matches    = $this->matchingService->findMatchesForLostReport($report, $foundItems);
        $top5       = array_slice($matches, 0, 5);

        // Simplify matches for JSON output
        $matchData = array_map(fn ($m) => [
            'score'      => $m['score'],
            'found_item' => $m['foundItem'],
            'reasons'    => $m['reasons'],
        ], $top5);

        return response()->json([
            'report'  => $report,
            'matches' => $matchData,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $report = $this->repository->findOrFail($id);

        return response()->json($report);
    }

    public function update(UpdateLostReportRequest $request, string $id): JsonResponse
    {
        $report = DB::transaction(fn () => $this->repository->update($id, $request->validated()));

        return response()->json($report);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:探索中,解決済,キャンセル'],
        ]);

        $report = DB::transaction(fn () => $this->repository->updateStatus($id, $validated['status']));

        return response()->json($report);
    }
}
