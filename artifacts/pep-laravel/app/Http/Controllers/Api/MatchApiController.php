<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\FoundItemRepository;
use App\Repositories\LostReportRepository;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchApiController extends Controller
{
    public function __construct(
        private readonly FoundItemRepository  $foundItemRepository,
        private readonly LostReportRepository $lostReportRepository,
        private readonly MatchingService      $matchingService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $foundItemId   = $request->query('foundItemId');
        $lostReportId  = $request->query('lostReportId');

        if ($foundItemId !== null) {
            // Matches for a specific found item against all searching lost reports
            $foundItem   = $this->foundItemRepository->findOrFail((string) $foundItemId);
            $lostReports = $this->lostReportRepository->allByStatus('探索中');

            $raw = $this->matchingService->findMatches($foundItem, $lostReports);
            $matches = array_map(fn ($m) => [
                'lost_report' => $m['lostReport'],
                'found_item'  => $foundItem,
                'score'       => $m['score'],
                'reasons'     => $m['reasons'],
            ], $raw);

            return response()->json($matches);
        }

        if ($lostReportId !== null) {
            // Matches for a specific lost report against all storing found items
            $lostReport = $this->lostReportRepository->findOrFail((string) $lostReportId);
            $foundItems = $this->foundItemRepository->allByStatus('保管中');

            $matches = $this->matchingService->findMatchesForLostReport($lostReport, $foundItems);
            $result = array_map(fn ($m) => [
                'lost_report' => $lostReport,
                'found_item'  => $m['foundItem'],
                'score'       => $m['score'],
                'reasons'     => $m['reasons'],
            ], $matches);

            return response()->json($result);
        }

        // All pairwise matches
        $foundItems  = $this->foundItemRepository->allByStatus('保管中');
        $lostReports = $this->lostReportRepository->allByStatus('探索中');

        $allMatches = $this->matchingService->computeAllMatches($lostReports, $foundItems, 30);
        $limited    = array_slice($allMatches, 0, 50);

        return response()->json($limited);
    }
}
