<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\FoundItemRepositoryInterface;

/**
 * Generates unique management numbers in the format YYYYMMDD-XXXX.
 *
 * The sequential part is 1-based and zero-padded to 4 digits.
 * Sequence resets each calendar day.
 *
 * Format: YYYYMMDD-XXXX (e.g. 20240101-0001)
 */
final class ManagementNumberService
{
    public function __construct(
        private readonly FoundItemRepositoryInterface $foundItemRepository,
    ) {}

    /**
     * Generate a management number for the given date.
     *
     * Uses the date as both the prefix and the dateFrom/dateTo filter to count
     * how many items were found on that calendar day, then increments by 1.
     *
     * @param \DateTimeInterface $date The date for which to generate a number.
     * @return string e.g. "20240101-0001"
     */
    public function generateForDate(\DateTimeInterface $date): string
    {
        $prefix   = $date->format('Ymd');
        $dateStr  = $date->format('Y-m-d');

        // Use the repository list() with date range filter to count existing entries.
        // The paginator's total() runs an efficient COUNT query without loading rows.
        $existing = $this->foundItemRepository->list(
            filters: ['dateFrom' => $dateStr, 'dateTo' => $dateStr],
            perPage: 1,
        )->total();

        $sequence = $existing + 1;

        return $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a management number for today.
     *
     * Convenience wrapper that passes the current date to {@see generateForDate()}.
     *
     * @return string e.g. "20240101-0001"
     */
    public function generate(): string
    {
        return $this->generateForDate(new \DateTimeImmutable('today'));
    }
}
