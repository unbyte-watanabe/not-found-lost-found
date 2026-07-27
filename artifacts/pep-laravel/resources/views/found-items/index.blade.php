@extends('layouts.app')
@section('title', '拾得物一覧')

@section('content')
<div class="page-header">
  <div>
    <h2 class="page-title">拾得物一覧</h2>
    <p class="page-subtitle">登録されたすべての拾得物</p>
  </div>
  <a href="{{ route('found-items.create') }}" class="btn btn-primary">
    <i data-lucide="plus"></i> 新規登録
  </a>
</div>

<div class="page-body">

  {{-- ===== Filter bar ===== --}}
  <form method="GET" action="{{ route('found-items.index') }}" id="filter-form">
    <div class="filter-bar">

      <div class="form-group">
        <label class="form-label" for="filter-status">ステータス</label>
        <select name="status" id="filter-status" class="form-control">
          <option value="">すべて</option>
          @foreach(['保管中','返還済','警察提出済','期間満了処分'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="filter-category">カテゴリ</label>
        <select name="category" id="filter-category" class="form-control">
          <option value="">すべて</option>
          @foreach(['財布・カバン類','衣類','電子機器','傘','その他'] as $c)
            <option value="{{ $c }}" {{ request('category') === $c ? 'selected' : '' }}>{{ $c }}</option>
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="filter-date-from">拾得日 (From)</label>
        <input type="date" name="date_from" id="filter-date-from"
               class="form-control" value="{{ request('date_from') }}">
      </div>

      <div class="form-group">
        <label class="form-label" for="filter-date-to">拾得日 (To)</label>
        <input type="date" name="date_to" id="filter-date-to"
               class="form-control" value="{{ request('date_to') }}">
      </div>

      <div class="form-group" style="flex:2; min-width:200px;">
        <label class="form-label" for="filter-keyword">キーワード</label>
        <input type="text" name="keyword" id="filter-keyword"
               class="form-control" placeholder="特徴・管理番号など"
               value="{{ request('keyword') }}">
      </div>

      <div class="flex gap-8 items-center" style="padding-bottom:1px">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="search"></i> 検索
        </button>
        <a href="{{ route('found-items.index') }}" class="btn btn-secondary">
          <i data-lucide="x"></i> クリア
        </a>
      </div>

    </div>
  </form>

  {{-- ===== Result count ===== --}}
  @if(isset($items) && $items->total() > 0)
    <p class="text-muted text-small mb-16">
      {{ number_format($items->total()) }}件見つかりました
    </p>
  @endif

  {{-- ===== Table ===== --}}
  @if(isset($items) && $items->count() > 0)
  <div class="table-wrap">
    <table class="table" aria-label="拾得物一覧テーブル">
      <thead>
        <tr>
          <th>管理番号</th>
          <th>カテゴリ</th>
          <th>サブカテゴリ</th>
          <th>特徴</th>
          <th>拾得日時</th>
          <th>ステータス</th>
          <th>保管場所</th>
          <th style="width:72px"></th>
        </tr>
      </thead>
      <tbody>
        @foreach($items as $item)
        <tr>
          <td class="table-mono">
            {{ $item->management_no }}
            @if($item->nearing_expiry ?? false)
              <span class="expiry-warn" title="期限間近">⚠️</span>
            @endif
          </td>
          <td>{{ $item->category }}</td>
          <td class="text-muted">{{ $item->sub_category ?? '—' }}</td>
          <td class="table-truncate" title="{{ $item->features }}">
            {{ $item->features }}
          </td>
          <td class="text-small text-muted" style="white-space:nowrap">
            {{ \Carbon\Carbon::parse($item->found_datetime)->format('Y/m/d H:i') }}
          </td>
          <td>
            <x-status-badge :status="$item->status" type="found" />
          </td>
          <td class="text-small text-muted">
            {{ $item->storage_location ?? '—' }}
          </td>
          <td>
            <a href="{{ route('found-items.show', $item->id) }}" class="btn btn-sm btn-secondary">
              詳細
            </a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($items->hasPages())
    <div class="pagination" role="navigation" aria-label="ページナビゲーション">
      {{-- Previous --}}
      @if($items->onFirstPage())
        <span class="disabled" aria-disabled="true">
          <i data-lucide="chevron-left" style="width:14px;height:14px"></i>
        </span>
      @else
        <a href="{{ $items->previousPageUrl() }}" aria-label="前のページ">
          <i data-lucide="chevron-left" style="width:14px;height:14px"></i>
        </a>
      @endif

      {{-- Page numbers --}}
      @foreach($items->getUrlRange(max(1,$items->currentPage()-2), min($items->lastPage(),$items->currentPage()+2)) as $page => $url)
        @if($page == $items->currentPage())
          <span class="active" aria-current="page">{{ $page }}</span>
        @else
          <a href="{{ $url }}">{{ $page }}</a>
        @endif
      @endforeach

      {{-- Next --}}
      @if($items->hasMorePages())
        <a href="{{ $items->nextPageUrl() }}" aria-label="次のページ">
          <i data-lucide="chevron-right" style="width:14px;height:14px"></i>
        </a>
      @else
        <span class="disabled" aria-disabled="true">
          <i data-lucide="chevron-right" style="width:14px;height:14px"></i>
        </span>
      @endif
    </div>
  @endif

  @else
  {{-- Empty state --}}
  <div class="empty-state">
    <div class="empty-state-icon">
      <i data-lucide="package-open" style="width:56px;height:56px"></i>
    </div>
    <h3>拾得物が見つかりません</h3>
    <p>
      @if(request()->hasAny(['status','category','date_from','date_to','keyword']))
        検索条件を変えて再度お試しください。
      @else
        まだ拾得物が登録されていません。
      @endif
    </p>
    @if(!request()->hasAny(['status','category','date_from','date_to','keyword']))
    <div style="margin-top:20px">
      <a href="{{ route('found-items.create') }}" class="btn btn-primary">
        <i data-lucide="plus"></i> 最初の拾得物を登録
      </a>
    </div>
    @endif
  </div>
  @endif

</div>{{-- /.page-body --}}
@endsection
