<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\FoundItem;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FoundItemRepositoryInterface
{
    /**
     * Find a found item by its UUID.
     */
    public function findById(string $id): ?FoundItem;

    /**
     * Paginated list of found items with optional filters.
     *
     * Supported filters:
     *   - status   (string)  : filter by status value
     *   - category (string)  : filter by category value
     *   - dateFrom (string)  : found_datetime >= dateFrom
     *   - dateTo   (string)  : found_datetime <= dateTo
     *   - keyword  (string)  : search in features, management_no, sub_category
     */
    public function list(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Create a new found item. management_no is generated automatically.
     */
    public function create(array $data): FoundItem;

    /**
     * Update an existing found item by UUID.
     */
    public function update(string $id, array $data): FoundItem;

    /**
     * Delete a found item by UUID.
     */
    public function delete(string $id): bool;

    /**
     * Update only the status (and optional extra fields) of a found item.
     */
    public function updateStatus(string $id, string $status, array $extra = []): FoundItem;

    /**
     * Count items by a specific status value.
     */
    public function countByStatus(string $status): int;

    /**
     * Count items whose found_datetime is today.
     */
    public function countToday(): int;

    /**
     * Count items that are storing and found more than 75 days ago.
     */
    public function countNearExpiry(): int;

    /**
     * Return last 7 days statistics: date => ['found' => int, 'returned' => int].
     */
    public function getWeeklyTrend(): array;

    /**
     * Get items eligible for police export, optionally filtered by date range.
     */
    public function getPoliceExportItems(?DateTimeInterface $from, ?DateTimeInterface $to): Collection;
}
