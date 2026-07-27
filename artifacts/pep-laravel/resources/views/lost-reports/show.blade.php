@extends('layouts.app')
@section('title', '紛失届詳細')

@section('content')
<div class="page-header">
  <div>
    <h2 class="page-title">
      紛失届詳細
      <x-status-badge :status="$report->status" type="lost" />
    </h2>
    <p class="page-subtitle">受付: {{ \Carbon\Carbon::parse($report->created_at)->format('Y年m月d日 H:i') }}</p>
  </div>
  <div class="flex gap-8 flex-wrap">
    <a href="{{ route('lost-reports.index') }}" class="btn btn-secondary">
      <i data-lucide="arrow-left"></i> 一覧へ戻る
    </a>
  </div>
</div>

<div class="page-body">

  <div style="display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start;">

    {{-- ===== Left: details ===== --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

      {{-- Lost item info --}}
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="search" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            紛失物情報
          </span>
        </div>
        <div class="card-body">
          <div class="detail-grid">
            <div class="detail-field">
              <label>カテゴリ</label>
              <p>{{ $report->category }}</p>
            </div>
            <div class="detail-field">
              <label>ステータス</label>
              <p><x-status-badge :status="$report->status" type="lost" /></p>
            </div>
            <div class="detail-field" style="grid-column:span 2">
              <label>特徴・外観</label>
              <p style="white-space:pre-wrap">{{ $report->features }}</p>
            </div>
            <div class="detail-field">
              <label>紛失日時 (From)</label>
              <p>
                @if($report->lost_datetime_from)
                  {{ \Carbon\Carbon::parse($report->lost_datetime_from)->format('Y年m月d日 H:i') }}
                @else
                  不明
                @endif
              </p>
            </div>
            <div class="detail-field">
              <label>紛失日時 (To)</label>
              <p>
                @if($report->lost_datetime_to)
                  {{ \Carbon\Carbon::parse($report->lost_datetime_to)->format('Y年m月d日 H:i') }}
                @else
                  —
                @endif
              </p>
            </div>
            <div class="detail-field" style="grid-column:span 2">
              <label>紛失場所（推定）</label>
              <p>{{ $report->lost_location_estimated ?: '—' }}</p>
            </div>
          </div>
        </div>
      </div>

      {{-- Owner info (masked) --}}
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="user" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            お客様情報
          </span>
          <button type="button" class="btn btn-sm btn-secondary" id="reveal-owner-btn"
                  aria-label="個人情報を表示">
            <i data-lucide="eye"></i> 表示
          </button>
        </div>
        <div class="card-body">
          <div class="detail-grid">
            <div class="detail-field">
              <label>お名前</label>
              <p id="owner-name-text" class="masked">{{ $report->owner_name }}</p>
            </div>
            <div class="detail-field">
              <label>連絡先</label>
              <p id="owner-contact-text" class="masked">{{ $report->owner_contact }}</p>
            </div>
          </div>
          <p class="form-hint mt-8">
            <i data-lucide="shield" style="width:12px;height:12px;vertical-align:middle;margin-right:3px"></i>
            個人情報は適切に管理してください
          </p>
        </div>
      </div>

      {{-- Match candidates --}}
      @php
        $matchList = $matches ?? session('matches') ?? [];
      @endphp
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="git-compare-arrows" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            マッチング候補
          </span>
          @if(count($matchList) > 0)
            <span class="badge badge-orange">{{ count($matchList) }}件</span>
          @endif
        </div>
        <div class="card-body" style="{{ count($matchList) > 0 ? 'padding:0' : '' }}">
          @if(count($matchList) > 0)
            @foreach($matchList as $match)
              @php
                $score = $match['score'] ?? 0;
                $gaugeClass = $score >= 80 ? 'high' : ($score >= 60 ? 'medium' : 'low');
                $foundItem = $match['foundItem'] ?? null;
              @endphp
              <div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; gap:14px; align-items:flex-start;">
                <div class="score-gauge {{ $gaugeClass }}" style="width:48px;height:48px;font-size:.9rem;flex-shrink:0">
                  {{ $score }}
                </div>
                <div style="flex:1">
                  @if($foundItem)
                    <div style="font-size:.88rem; font-weight:600; margin-bottom:3px;">
                      {{ $foundItem->management_no ?? '' }}
                      – {{ $foundItem->category ?? '' }}
                    </div>
                    <div class="text-small text-muted" style="margin-bottom:6px">
                      {{ mb_substr($foundItem->features ?? '', 0, 60) }}{{ mb_strlen($foundItem->features ?? '') > 60 ? '...' : '' }}
                    </div>
                    <div class="text-small text-muted">
                      拾得: {{ isset($foundItem->found_datetime) ? \Carbon\Carbon::parse($foundItem->found_datetime)->format('Y/m/d H:i') : '—' }}
                      {{ $foundItem->found_location ? ' @ ' . $foundItem->found_location : '' }}
                    </div>
                  @endif
                  @if(!empty($match['reasons']))
                    <div class="match-reasons" style="margin-top:6px">
                      @foreach($match['reasons'] as $reason)
                        <span class="match-reason-tag">{{ $reason }}</span>
                      @endforeach
                    </div>
                  @endif
                </div>
                @if($foundItem)
                  <a href="{{ route('found-items.show', $foundItem->id ?? '#') }}"
                     class="btn btn-sm btn-secondary" style="flex-shrink:0">
                    詳細を見る
                  </a>
                @endif
              </div>
            @endforeach
          @else
            <div class="empty-state" style="padding:28px 16px">
              <div class="empty-state-icon" style="width:36px;height:36px">
                <i data-lucide="search-x" style="width:36px;height:36px"></i>
              </div>
              <h3 style="font-size:.85rem">候補なし</h3>
              <p style="font-size:.78rem">現在マッチする拾得物が見つかりません</p>
            </div>
          @endif
        </div>
        @if(count($matchList) > 0)
        <div class="card-footer">
          <a href="{{ route('matches.index') }}?lost_report_id={{ $report->id }}"
             class="btn btn-sm btn-secondary">
            <i data-lucide="external-link"></i> マッチング一覧で確認
          </a>
        </div>
        @endif
      </div>

    </div>{{-- /.left --}}

    {{-- ===== Right: actions ===== --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

      {{-- Status actions (only for 探索中) --}}
      @if($report->status === '探索中')
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="settings" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            ステータス変更
          </span>
        </div>
        <div class="card-body" style="display:flex; flex-direction:column; gap:10px">
          <form method="POST" action="{{ route('lost-reports.update-status', $report->id) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="解決済">
            <button type="submit" class="btn btn-success w-full" style="justify-content:center"
                    data-confirm="解決済みに変更しますか？">
              <i data-lucide="check-circle"></i> 解決済みにする
            </button>
          </form>
          <form method="POST" action="{{ route('lost-reports.update-status', $report->id) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="キャンセル">
            <button type="submit" class="btn btn-secondary w-full" style="justify-content:center"
                    data-confirm="この紛失届をキャンセルしますか？">
              <i data-lucide="x-circle"></i> キャンセルする
            </button>
          </form>
        </div>
      </div>
      @endif

      {{-- Matching --}}
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="git-compare-arrows" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            マッチング
          </span>
        </div>
        <div class="card-body">
          <a href="{{ route('matches.index') }}?lost_report_id={{ $report->id }}"
             class="btn btn-secondary w-full" style="justify-content:center">
            <i data-lucide="search"></i> マッチング候補を確認
          </a>
        </div>
      </div>

      {{-- Delete (disabled — 紛失届は削除不可。ステータスを「キャンセル」に変更してください。) --}}

      {{-- Timestamps --}}
      <div class="text-small text-muted" style="padding:0 4px; line-height:2">
        <div>受付: {{ \Carbon\Carbon::parse($report->created_at)->format('Y/m/d H:i') }}</div>
        <div>更新: {{ \Carbon\Carbon::parse($report->updated_at)->format('Y/m/d H:i') }}</div>
      </div>

    </div>{{-- /.right --}}
  </div>{{-- /.grid --}}

</div>{{-- /.page-body --}}
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var revealBtn     = document.getElementById('reveal-owner-btn');
  var nameText      = document.getElementById('owner-name-text');
  var contactText   = document.getElementById('owner-contact-text');
  var revealed      = false;

  if (revealBtn && nameText && contactText) {
    revealBtn.addEventListener('click', function () {
      revealed = !revealed;
      nameText.classList.toggle('masked', !revealed);
      nameText.classList.toggle('revealed', revealed);
      contactText.classList.toggle('masked', !revealed);
      contactText.classList.toggle('revealed', revealed);
      revealBtn.innerHTML = revealed
        ? '<i data-lucide="eye-off"></i> 非表示'
        : '<i data-lucide="eye"></i> 表示';
      if (window.lucide) lucide.createIcons();
    });
  }
});
</script>
@endpush
