@extends('layouts.app')
@section('title', '紛失届一覧')

@section('content')
<div class="page-header">
  <div>
    <h2 class="page-title">紛失届一覧</h2>
    <p class="page-subtitle">受付済みの紛失届</p>
  </div>
  <a href="{{ route('lost-reports.create') }}" class="btn btn-primary">
    <i data-lucide="plus"></i> 新規登録
  </a>
</div>

<div class="page-body">

  {{-- ===== Filter bar ===== --}}
  <form method="GET" action="{{ route('lost-reports.index') }}">
    <div class="filter-bar">

      <div class="form-group">
        <label class="form-label" for="filter-status">ステータス</label>
        <select name="status" id="filter-status" class="form-control">
          <option value="">すべて</option>
          @foreach(['探索中','解決済','キャンセル'] as $s)
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

      <div class="form-group" style="flex:2; min-width:200px;">
        <label class="form-label" for="filter-keyword">キーワード</label>
        <input type="text" name="keyword" id="filter-keyword"
               class="form-control" placeholder="氏名・特徴など"
               value="{{ request('keyword') }}">
      </div>

      <div class="flex gap-8 items-center" style="padding-bottom:1px">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="search"></i> 検索
        </button>
        <a href="{{ route('lost-reports.index') }}" class="btn btn-secondary">
          <i data-lucide="x"></i> クリア
        </a>
      </div>

    </div>
  </form>

  {{-- Result count --}}
  @if(isset($reports) && $reports->total() > 0)
    <p class="text-muted text-small mb-16">
      {{ number_format($reports->total()) }}件見つかりました
    </p>
  @endif

  {{-- ===== Table ===== --}}
  @if(isset($reports) && $reports->count() > 0)
  <div class="table-wrap">
    <table class="table" aria-label="紛失届一覧テーブル">
      <thead>
        <tr>
          <th>受付日時</th>
          <th>氏名</th>
          <th>カテゴリ</th>
          <th>特徴</th>
          <th>紛失日時</th>
          <th>ステータス</th>
          <th style="width:72px"></th>
        </tr>
      </thead>
      <tbody>
        @foreach($reports as $report)
        <tr>
          <td class="text-small text-muted" style="white-space:nowrap">
            {{ \Carbon\Carbon::parse($report->created_at)->format('Y/m/d H:i') }}
          </td>
          <td>
            {{-- Masked by default --}}
            <span style="filter:blur(4px); user-select:none; font-size:.85rem"
                  title="詳細ページで確認できます">
              {{ mb_substr($report->owner_name, 0, 1) }}●●
            </span>
          </td>
          <td>{{ $report->category }}</td>
          <td class="table-truncate" title="{{ $report->features }}">
            {{ $report->features }}
          </td>
          <td class="text-small text-muted" style="white-space:nowrap">
            @if($report->lost_datetime_from)
              {{ \Carbon\Carbon::parse($report->lost_datetime_from)->format('Y/m/d') }}
              @if($report->lost_datetime_to)
                〜{{ \Carbon\Carbon::parse($report->lost_datetime_to)->format('m/d') }}
              @endif
            @else
              —
            @endif
          </td>
          <td>
            <x-status-badge :status="$report->status" type="lost" />
          </td>
          <td>
            <a href="{{ route('lost-reports.show', $report->id) }}" class="btn btn-sm btn-secondary">
              詳細
            </a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($reports->hasPages())
    <div class="pagination" role="navigation" aria-label="ページナビゲーション">
      @if($reports->onFirstPage())
        <span class="disabled" aria-disabled="true">
          <i data-lucide="chevron-left" style="width:14px;height:14px"></i>
        </span>
      @else
        <a href="{{ $reports->previousPageUrl() }}" aria-label="前のページ">
          <i data-lucide="chevron-left" style="width:14px;height:14px"></i>
        </a>
      @endif

      @foreach($reports->getUrlRange(max(1,$reports->currentPage()-2), min($reports->lastPage(),$reports->currentPage()+2)) as $page => $url)
        @if($page == $reports->currentPage())
          <span class="active" aria-current="page">{{ $page }}</span>
        @else
          <a href="{{ $url }}">{{ $page }}</a>
        @endif
      @endforeach

      @if($reports->hasMorePages())
        <a href="{{ $reports->nextPageUrl() }}" aria-label="次のページ">
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
      <i data-lucide="file-x" style="width:56px;height:56px"></i>
    </div>
    <h3>紛失届が見つかりません</h3>
    <p>
      @if(request()->hasAny(['status','category','keyword']))
        検索条件を変えて再度お試しください。
      @else
        まだ紛失届が登録されていません。
      @endif
    </p>
    @if(!request()->hasAny(['status','category','keyword']))
    <div style="margin-top:20px">
      <a href="{{ route('lost-reports.create') }}" class="btn btn-primary">
        <i data-lucide="plus"></i> 最初の紛失届を登録
      </a>
    </div>
    @endif
  </div>
  @endif

</div>{{-- /.page-body --}}
@endsection
