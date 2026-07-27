@extends('layouts.app')
@section('title', 'マッチング確認')

@section('content')
<div class="page-header">
  <div>
    <h2 class="page-title">マッチング確認</h2>
    <p class="page-subtitle">拾得物と紛失届の照合結果</p>
  </div>
  @if(isset($matches) && count($matches) > 0)
    <span class="badge badge-orange" style="font-size:.85rem; padding:6px 12px">
      {{ count($matches) }} 件のマッチ候補
    </span>
  @endif
</div>

<div class="page-body">

  {{-- Score legend --}}
  <div class="flex gap-16 mb-20 flex-wrap" style="align-items:center">
    <span class="text-small text-muted" style="font-weight:600">スコア凡例：</span>
    <span class="flex items-center gap-8 text-small">
      <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:var(--yellow);border:2px solid var(--yellow)"></span>
      30〜59: 低い一致
    </span>
    <span class="flex items-center gap-8 text-small">
      <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:var(--accent);border:2px solid var(--accent)"></span>
      60〜79: 中程度の一致
    </span>
    <span class="flex items-center gap-8 text-small">
      <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:var(--red);border:2px solid var(--red)"></span>
      80〜: 高い一致
    </span>
  </div>

  {{-- Filter (optional) --}}
  @if(request()->has('lost_report_id') || request()->has('found_item_id'))
  <div class="alert alert-info mb-20" role="alert">
    <i data-lucide="filter" style="width:14px;height:14px;flex-shrink:0"></i>
    <span>
      絞り込み表示中。
      <a href="{{ route('matches.index') }}">すべてのマッチを表示</a>
    </span>
  </div>
  @endif

  {{-- ===== Match cards ===== --}}
  @if(isset($matches) && count($matches) > 0)

    @foreach($matches as $match)
      @php
        $score      = $match['score'] ?? 0;
        $gaugeClass = $score >= 80 ? 'high' : ($score >= 60 ? 'medium' : 'low');
        $foundItem  = $match['foundItem']  ?? null;
        $lostReport = $match['lostReport'] ?? null;
        $reasons    = $match['reasons']    ?? [];
      @endphp

      <div class="match-card">

        {{-- Score gauge --}}
        <div class="score-gauge {{ $gaugeClass }}" aria-label="マッチスコア {{ $score }}点">
          {{ $score }}
        </div>

        {{-- Main content --}}
        <div class="match-card-body">

          <div class="flex items-center justify-between flex-wrap gap-8" style="margin-bottom:10px">
            <h3 style="font-size:.95rem; font-weight:700; color:var(--text)">
              マッチ候補
              @if($score >= 80)
                <span class="badge badge-red" style="margin-left:8px">高一致</span>
              @elseif($score >= 60)
                <span class="badge badge-orange" style="margin-left:8px">中一致</span>
              @else
                <span class="badge badge-yellow" style="margin-left:8px">低一致</span>
              @endif
            </h3>
          </div>

          {{-- Two-column info grid --}}
          <div class="match-info-grid">

            {{-- Found item --}}
            <div class="match-info-col">
              <h4>
                <i data-lucide="package" style="width:11px;height:11px;vertical-align:middle;margin-right:3px"></i>
                拾得物
              </h4>
              @if($foundItem)
                <div style="font-size:.83rem; margin-bottom:4px;">
                  <span class="font-mono" style="font-size:.78rem; color:var(--text-muted)">{{ $foundItem->management_no }}</span>
                </div>
                <div style="font-size:.85rem; font-weight:600; margin-bottom:4px;">
                  {{ $foundItem->category }}
                  @if($foundItem->sub_category)
                    <span class="text-muted" style="font-weight:400">/ {{ $foundItem->sub_category }}</span>
                  @endif
                </div>
                <div class="text-small text-muted" style="margin-bottom:4px; line-height:1.5">
                  {{ mb_substr($foundItem->features, 0, 80) }}{{ mb_strlen($foundItem->features) > 80 ? '...' : '' }}
                </div>
                <div class="text-small text-muted">
                  📅 {{ \Carbon\Carbon::parse($foundItem->found_datetime)->format('Y/m/d H:i') }}
                  @if($foundItem->found_location)
                    <br>📍 {{ $foundItem->found_location }}
                  @endif
                </div>
                <div style="margin-top:8px">
                  <x-status-badge :status="$foundItem->status" type="found" />
                </div>
              @else
                <p class="text-small text-muted">情報なし</p>
              @endif
            </div>

            {{-- Lost report --}}
            <div class="match-info-col">
              <h4>
                <i data-lucide="file-search" style="width:11px;height:11px;vertical-align:middle;margin-right:3px"></i>
                紛失届
              </h4>
              @if($lostReport)
                <div style="font-size:.85rem; font-weight:600; margin-bottom:4px;">
                  {{ $lostReport->category }}
                </div>
                <div class="text-small text-muted" style="margin-bottom:4px; line-height:1.5">
                  {{ mb_substr($lostReport->features, 0, 80) }}{{ mb_strlen($lostReport->features) > 80 ? '...' : '' }}
                </div>
                <div class="text-small text-muted">
                  @if($lostReport->lost_datetime_from)
                    📅 {{ \Carbon\Carbon::parse($lostReport->lost_datetime_from)->format('Y/m/d H:i') }}
                    @if($lostReport->lost_datetime_to)
                      〜{{ \Carbon\Carbon::parse($lostReport->lost_datetime_to)->format('m/d H:i') }}
                    @endif
                  @else
                    📅 日時不明
                  @endif
                  @if($lostReport->lost_location_estimated)
                    <br>📍 {{ $lostReport->lost_location_estimated }}
                  @endif
                </div>
                <div style="margin-top:8px">
                  <x-status-badge :status="$lostReport->status" type="lost" />
                </div>
              @else
                <p class="text-small text-muted">情報なし</p>
              @endif
            </div>

          </div>{{-- /.match-info-grid --}}

          {{-- Match reasons --}}
          @if(count($reasons) > 0)
          <div class="match-reasons" style="margin-top:12px">
            <span class="text-small text-muted" style="font-weight:600; margin-right:4px; align-self:center">
              <i data-lucide="tag" style="width:11px;height:11px;vertical-align:middle"></i>
              一致理由:
            </span>
            @foreach($reasons as $reason)
              <span class="match-reason-tag">{{ $reason }}</span>
            @endforeach
          </div>
          @endif

          {{-- Action buttons --}}
          <div class="flex gap-8 mt-16 flex-wrap">
            @if($foundItem)
              <a href="{{ route('found-items.show', $foundItem->id) }}"
                 class="btn btn-sm btn-secondary">
                <i data-lucide="package-search"></i> 拾得物詳細
              </a>
            @endif
            @if($lostReport)
              <a href="{{ route('lost-reports.show', $lostReport->id) }}"
                 class="btn btn-sm btn-secondary">
                <i data-lucide="file-search"></i> 紛失届詳細
              </a>
            @endif
          </div>

        </div>{{-- /.match-card-body --}}
      </div>{{-- /.match-card --}}
    @endforeach

  @else
  {{-- Empty state --}}
  <div class="empty-state">
    <div class="empty-state-icon">
      <i data-lucide="git-compare-arrows" style="width:56px;height:56px"></i>
    </div>
    <h3>マッチ候補がありません</h3>
    <p>
      現在、スコア30以上のマッチング候補が見つかりませんでした。<br>
      拾得物・紛失届の特徴をより詳細に入力するとマッチング精度が向上します。
    </p>
    <div style="margin-top:24px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap">
      <a href="{{ route('found-items.index') }}" class="btn btn-secondary">
        <i data-lucide="package-search"></i> 拾得物一覧
      </a>
      <a href="{{ route('lost-reports.index') }}" class="btn btn-secondary">
        <i data-lucide="file-search"></i> 紛失届一覧
      </a>
    </div>
  </div>
  @endif

</div>{{-- /.page-body --}}
@endsection
