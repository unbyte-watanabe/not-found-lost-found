<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\FoundItemRepository;
use App\Repositories\LostReportRepository;
use App\Services\MatchingService;
use Illuminate\View\View;

class MatchController extends Controller
{
    public function __construct(
        private readonly FoundItemRepository  $foundItemRepository,
        private readonly LostReportRepository $lostReportRepository,
        private readonly MatchingService      $matchingService,
    ) {
    }

    public function index(): View
    {
        $foundItems  = $this->foundItemRepository->allByStatus('保管中');
        $lostReports = $this->lostReportRepository->allByStatus('探索中');

        $allMatches = $this->matchingService->computeAllMatches($lostReports, $foundItems, 30);

        // Limit to top 50, already sorted by score desc
        $matches = array_slice($allMatches, 0, 50);

        return view('matches.index', compact('matches'));
    }
}
