<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LostReport;
use App\Repositories\Contracts\LostReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LostReportRepository implements LostReportRepositoryInterface
{
    public function __construct(private readonly LostReport $model)
    {
    }

    public function findById(string $id): ?LostReport
    {
        return $this->model->newQuery()->find($id);
    }

    public function findOrFail(string $id): LostReport
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed> $filters
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $query->where(function ($q) use ($kw): void {
                $q->where('features', 'like', '%' . $kw . '%')
                  ->orWhere('owner_name', 'like', '%' . $kw . '%')
                  ->orWhere('lost_location_estimated', 'like', '%' . $kw . '%');
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Alias used by controllers.
     *
     * @param  array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->list($filters, $perPage);
    }

    /**
     * @param  array<string, mixed> $data
     */
    public function create(array $data): LostReport
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * @param  array<string, mixed> $data
     */
    public function update(string $id, array $data): LostReport
    {
        $report = $this->findOrFail($id);
        $report->update($data);
        return $report->fresh();
    }

    public function updateStatus(string $id, string $status): LostReport
    {
        $report = $this->findOrFail($id);
        $report->update(['status' => $status]);
        return $report->fresh();
    }

    public function countActive(): int
    {
        return $this->model->newQuery()->where('status', '探索中')->count();
    }

    public function getActiveReports(): Collection
    {
        return $this->model->newQuery()
            ->where('status', '探索中')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function allByStatus(string $status): Collection
    {
        return $this->model->newQuery()
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
