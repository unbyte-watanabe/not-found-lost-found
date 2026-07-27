<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\FoundItem;
use App\Repositories\Contracts\FoundItemRepositoryInterface;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class FoundItemRepository implements FoundItemRepositoryInterface
{
    public function __construct(private readonly FoundItem $model)
    {
    }

    public function findById(string $id): ?FoundItem
    {
        return $this->model->newQuery()->find($id);
    }

    public function findOrFail(string $id): FoundItem
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed> $filters
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->orderBy('found_datetime', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['dateFrom'])) {
            $query->where('found_datetime', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('found_datetime', '<=', $filters['dateTo'] . ' 23:59:59');
        }

        if (!empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $query->where(function ($q) use ($kw): void {
                $q->where('features', 'like', '%' . $kw . '%')
                  ->orWhere('found_location', 'like', '%' . $kw . '%')
                  ->orWhere('management_no', 'like', '%' . $kw . '%')
                  ->orWhere('sub_category', 'like', '%' . $kw . '%');
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Alias used by controllers passing filters directly.
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
    public function create(array $data): FoundItem
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * @param  array<string, mixed> $data
     */
    public function update(string $id, array $data): FoundItem
    {
        $item = $this->findOrFail($id);
        $item->update($data);
        return $item->fresh();
    }

    public function delete(string $id): bool
    {
        $item = $this->findOrFail($id);
        return (bool) $item->delete();
    }

    /**
     * @param  array<string, mixed> $extra
     */
    public function updateStatus(string $id, string $status, array $extra = []): FoundItem
    {
        $item = $this->findOrFail($id);
        $item->update(array_merge(['status' => $status], $extra));
        return $item->fresh();
    }

    public function countByStatus(string $status): int
    {
        return $this->model->newQuery()->where('status', $status)->count();
    }

    public function countToday(): int
    {
        return $this->model->newQuery()
            ->whereDate('found_datetime', Carbon::today())
            ->count();
    }

    public function countNearExpiry(): int
    {
        $threshold = Carbon::today()->subDays(75);
        return $this->model->newQuery()
            ->where('status', '保管中')
            ->where('found_datetime', '<=', $threshold)
            ->count();
    }

    public function getWeeklyTrend(): array
    {
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date   = Carbon::today()->subDays($i)->toDateString();
            $found  = $this->model->newQuery()->whereDate('found_datetime', $date)->count();
            $returned = $this->model->newQuery()
                ->where('status', '返還済')
                ->whereDate('updated_at', $date)
                ->count();
            $trend[$date] = ['found' => $found, 'returned' => $returned];
        }
        return $trend;
    }

    public function getPoliceExportItems(?DateTimeInterface $from, ?DateTimeInterface $to): Collection
    {
        $query = $this->model->newQuery()->orderBy('found_datetime', 'desc');

        if ($from !== null) {
            $query->where('found_datetime', '>=', $from);
        }

        if ($to !== null) {
            $query->where('found_datetime', '<=', $to);
        }

        return $query->get();
    }

    /**
     * All found items with a given status.
     */
    public function allByStatus(string $status): Collection
    {
        return $this->model->newQuery()
            ->where('status', $status)
            ->orderBy('found_datetime', 'desc')
            ->get();
    }

    /**
     * Items for export with simple array filters.
     *
     * @param  array<string, mixed> $filters
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = $this->model->newQuery()->orderBy('found_datetime', 'desc');

        if (!empty($filters['dateFrom'])) {
            $query->where('found_datetime', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('found_datetime', '<=', $filters['dateTo'] . ' 23:59:59');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }
}
