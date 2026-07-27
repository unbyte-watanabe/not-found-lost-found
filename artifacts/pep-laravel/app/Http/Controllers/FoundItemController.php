<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreFoundItemRequest;
use App\Http\Requests\UpdateFoundItemRequest;
use App\Repositories\FoundItemRepository;
use App\Services\ManagementNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FoundItemController extends Controller
{
    public function __construct(
        private readonly FoundItemRepository    $repository,
        private readonly ManagementNumberService $managementNumberService,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'category', 'dateFrom', 'dateTo', 'keyword']);
        $items   = $this->repository->paginate($filters, 20);

        return view('found-items.index', compact('items', 'filters'));
    }

    public function create(): View
    {
        return view('found-items.create');
    }

    public function store(StoreFoundItemRequest $request): RedirectResponse
    {
        $item = DB::transaction(function () use ($request) {
            $managementNo = $this->managementNumberService->generate();

            return $this->repository->create(
                array_merge($request->validated(), [
                    'management_no' => $managementNo,
                    'status'        => '保管中',
                ])
            );
        });

        return redirect()
            ->route('found-items.show', $item->id)
            ->with('success', '落とし物を登録しました。管理番号: ' . $item->management_no);
    }

    public function show(string $id): View
    {
        $item = $this->repository->findOrFail($id);

        return view('found-items.show', compact('item'));
    }

    public function edit(string $id): View
    {
        $item = $this->repository->findOrFail($id);

        return view('found-items.edit', compact('item'));
    }

    public function update(UpdateFoundItemRequest $request, string $id): RedirectResponse
    {
        $item = DB::transaction(fn () => $this->repository->update($id, $request->validated()));

        return redirect()
            ->route('found-items.show', $item->id)
            ->with('success', '落とし物情報を更新しました。');
    }

    public function destroy(string $id): RedirectResponse
    {
        DB::transaction(fn () => $this->repository->delete($id));

        return redirect()
            ->route('found-items.index')
            ->with('success', '落とし物を削除しました。');
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:保管中,返還済,警察提出済,期間満了処分'],
        ]);

        $item = DB::transaction(fn () => $this->repository->updateStatus($id, $validated['status']));

        return response()->json([
            'success' => true,
            'item'    => $item,
        ]);
    }
}
