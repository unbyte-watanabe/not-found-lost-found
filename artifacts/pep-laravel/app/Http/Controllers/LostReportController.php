<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLostReportRequest;
use App\Http\Requests\UpdateLostReportRequest;
use App\Repositories\FoundItemRepository;
use App\Repositories\LostReportRepository;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LostReportController extends Controller
{
    public function __construct(
        private readonly LostReportRepository $repository,
        private readonly FoundItemRepository  $foundItemRepository,
        private readonly MatchingService      $matchingService,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'category', 'keyword']);
        $reports = $this->repository->paginate($filters, 20);

        return view('lost-reports.index', compact('reports', 'filters'));
    }

    public function create(): View
    {
        return view('lost-reports.create');
    }

    public function store(StoreLostReportRequest $request): RedirectResponse
    {
        $report = DB::transaction(function () use ($request) {
            return $this->repository->create(
                array_merge($request->validated(), ['status' => '探索中'])
            );
        });

        // Find top 5 matches among 保管中 found items
        $foundItems = $this->foundItemRepository->allByStatus('保管中');
        $matches    = $this->matchingService->findMatchesForLostReport($report, $foundItems);
        $matches    = array_slice($matches, 0, 5);

        return redirect()
            ->route('lost-reports.show', $report->id)
            ->with('success', '遺失物届を受け付けました。')
            ->with('match_candidates', $matches);
    }

    public function show(string $id): View
    {
        $report           = $this->repository->findOrFail($id);
        $matchCandidates  = session('match_candidates', []);

        return view('lost-reports.show', compact('report', 'matchCandidates'));
    }

    public function edit(string $id): View
    {
        $report = $this->repository->findOrFail($id);

        return view('lost-reports.edit', compact('report'));
    }

    public function update(UpdateLostReportRequest $request, string $id): RedirectResponse
    {
        $report = DB::transaction(fn () => $this->repository->update($id, $request->validated()));

        return redirect()
            ->route('lost-reports.show', $report->id)
            ->with('success', '遺失物届を更新しました。');
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:探索中,解決済,キャンセル'],
        ]);

        $report = DB::transaction(fn () => $this->repository->updateStatus($id, $validated['status']));

        return response()->json([
            'success' => true,
            'report'  => $report,
        ]);
    }
}
