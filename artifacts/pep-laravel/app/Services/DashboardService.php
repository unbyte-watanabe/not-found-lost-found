<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\FoundItemRepositoryInterface;
use App\Repositories\Contracts\LostReportRepositoryInterface;

/**
 * Provides aggregated statistics and trend data for the dashboard.
 */
final class DashboardService
{
    public function __construct(
        private readonly FoundItemRepositoryInterface  $foundItemRepository,
        private readonly LostReportRepositoryInterface $lostReportRepository,
    ) {}

    /**
     * Retrieve key operational statistics.
     *
     * @return array{
     *     storing: int,
     *     todayFound: int,
     *     nearExpiry: int,
     *     monthlyReturned: int,
     *     activeReports: int,
     * }
     */
    public function getStats(): array
    {
        return [
            'storing'         => $this->foundItemRepository->countByStatus('保管中'),
            'todayFound'      => $this->foundItemRepository->countToday(),
            'nearExpiry'      => $this->foundItemRepository->countNearExpiry(),
            'monthlyReturned' => $this->foundItemRepository->countByStatus('返還済'),
            'activeReports'   => $this->lostReportRepository->countActive(),
        ];
    }

    /**
     * Retrieve daily found-item and returned-item counts for the last 7 days.
     *
     * Each entry contains:
     *  - date:     formatted as "n/j" (e.g. "1/5")
     *  - found:    number of items found on that day
     *  - returned: number of items returned on that day
     *
     * @return list<array{date: string, found: int, returned: int}>
     */
    public function getWeeklyTrend(): array
    {
        // Repository returns ['YYYY-MM-DD' => ['found' => int, 'returned' => int]]
        $raw = $this->foundItemRepository->getWeeklyTrend();

        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $carbon  = now()->subDays($i);
            $dateKey = $carbon->format('Y-m-d');
            $label   = $carbon->format('n/j');

            $dayData = $raw[$dateKey] ?? [];

            $trend[] = [
                'date'     => $label,
                'found'    => (int) ($dayData['found']    ?? 0),
                'returned' => (int) ($dayData['returned'] ?? 0),
            ];
        }

        return $trend;
    }
}
