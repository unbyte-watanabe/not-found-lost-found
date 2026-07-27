<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\LostReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface LostReportRepositoryInterface
{
    /**
     * Find a lost report by its UUID.
     */
    public function findById(string $id): ?LostReport;

    /**
     * Paginated list of lost reports with optional filters.
     *
     * Supported filters:
     *   - status   (string) : filter by status value
     *   - category (string) : filter by category value
     *   - keyword  (string) : search in features, owner_name
     */
    public function list(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Create a new lost report.
     */
    public function create(array $data): LostReport;

    /**
     * Update an existing lost report by UUID.
     */
    public function update(string $id, array $data): LostReport;

    /**
     * Update only the status of a lost report.
     */
    public function updateStatus(string $id, string $status): LostReport;

    /**
     * Count active (探索中) lost reports.
     */
    public function countActive(): int;

    /**
     * Get all active (探索中) lost reports for matching purposes.
     */
    public function getActiveReports(): Collection;
}
