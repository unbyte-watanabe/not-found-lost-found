@extends('layouts.app')
@section('title', 'ダッシュボード')

@section('content')
<div class="page-header">
  <div>
    <h2 class="page-title">ダッシュボード</h2>
    <p class="page-subtitle">落とし物管理の概況</p>
  </div>
  <div class="flex gap-8">
    <a href="{{ route('found-items.create') }}" class="btn btn-primary">
      <i data-lucide="plus"></i> 拾得物を登録
    </a>
  </div>
</div>

<div class="page-body">

  {{-- ===== Stat Cards ===== --}}
  <div class="stat-grid">
    <div class="stat-card">
      <div class="stat-card-icon orange">
        <i data-lucide="package" style="width:20px;height:20px"></i>
      </div>
      <div class="stat-card-label">現在保管中</div>
      <div class="stat-card-value">{{ $stats['storing'] ?? 0 }}</div>
      <div class="stat-card-sub">件の拾得物を管理中</div>
    </div>

    <div class="stat-card">
      <div class="stat-card-icon green">
        <i data-lucide="calendar-plus" style="width:20px;height:20px"></i>
      </div>
      <div class="stat-card-label">本日拾得</div>
      <div class="stat-card-value">{{ $stats['todayFound'] ?? 0 }}</div>
      <div class="stat-card-sub">本日受付した拾得物</div>
    </div>

    <div class="stat-card">
      <div class="stat-card-icon red">
        <i data-lucide="clock-alert" style="width:20px;height:20px"></i>
      </div>
      <div class="stat-card-label">期限間近</div>
      <div class="stat-card-value">{{ $stats['nearExpiry'] ?? 0 }}</div>
      <div class="stat-card-sub">7日以内に期限切れ</div>
    </div>

    <div class="stat-card">
      <div class="stat-card-icon blue">
        <i data-lucide="handshake" style="width:20px;height:20px"></i>
      </div>
      <div class="stat-card-label">今月の返還</div>
      <div class="stat-card-value">{{ $stats['monthlyReturned'] ?? 0 }}</div>
      <div class="stat-card-sub">件を返還済み</div>
    </div>
  </div>

  {{-- ===== Quick Actions ===== --}}
  <div class="quick-actions">
    <a href="{{ route('found-items.create') }}" class="btn btn-primary btn-lg">
      <i data-lucide="plus-circle"></i> 拾得物を登録
    </a>
    <a href="{{ route('lost-reports.create') }}" class="btn btn-secondary btn-lg">
      <i data-lucide="file-plus"></i> 紛失届を作成
    </a>
    <a href="{{ route('matches.index') }}" class="btn btn-secondary btn-lg">
      <i data-lucide="git-compare-arrows"></i> マッチング確認
    </a>
  </div>

  {{-- ===== Main grid: chart + tasks ===== --}}
  <div style="display:grid; grid-template-columns: 1fr 340px; gap:20px; align-items:start;">

    {{-- Weekly trend chart --}}
    <div class="card">
      <div class="card-header">
        <span class="card-title">
          <i data-lucide="trending-up" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
          週間拾得物推移
        </span>
        <span class="text-small text-muted">過去7日間</span>
      </div>
      <div class="card-body">
        <div class="chart-wrap">
          <canvas id="trend-chart" aria-label="週間拾得物推移グラフ" role="img"></canvas>
        </div>
      </div>
    </div>

    {{-- Tasks / status panel --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

      {{-- Unconfirmed matches --}}
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="bell" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            タスク
          </span>
        </div>
        <div class="card-body" style="padding:0">
          <ul class="task-list" style="padding:0 20px">
            <li>
              <span class="flex items-center gap-8">
                <span class="task-dot" style="background:var(--accent)"></span>
                未確認マッチング
              </span>
              <a href="{{ route('matches.index') }}" class="badge badge-orange">
                確認する <i data-lucide="arrow-right" style="width:11px;height:11px"></i>
              </a>
            </li>
            <li>
              <span class="flex items-center gap-8">
                <span class="task-dot" style="background:var(--blue)"></span>
                探索中の紛失届
              </span>
              <a href="{{ route('lost-reports.index') }}?status=探索中" class="badge badge-blue">
                {{ $stats['activeReports'] ?? 0 }}件
              </a>
            </li>
            @if(($stats['nearExpiry'] ?? 0) > 0)
            <li>
              <span class="flex items-center gap-8">
                <span class="task-dot" style="background:var(--red)"></span>
                期限間近の拾得物
              </span>
              <a href="{{ route('found-items.index') }}?near_expiry=1" class="badge badge-red">
                {{ $stats['nearExpiry'] }}件
              </a>
            </li>
            @endif
          </ul>
        </div>
      </div>

      {{-- Category breakdown --}}
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="pie-chart" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            カテゴリ別内訳
          </span>
        </div>
        <div class="card-body" style="padding:12px 20px">
          @php
            $categoryColors = [
              '財布・カバン類' => ['badge-orange', '👛'],
              '衣類'         => ['badge-blue',   '👕'],
              '電子機器'     => ['badge-gray',   '📱'],
              '傘'           => ['badge-green',  '☂️'],
              'その他'       => ['badge-yellow', '📦'],
            ];
          @endphp
          @if(!empty($stats['byCategory']))
            @foreach($stats['byCategory'] as $cat => $count)
              @php [$cls, $icon] = $categoryColors[$cat] ?? ['badge-gray', '📦']; @endphp
              <div class="flex items-center justify-between" style="padding:6px 0; border-bottom:1px solid var(--border);">
                <span style="font-size:.83rem;">{{ $icon }} {{ $cat }}</span>
                <span class="badge {{ $cls }}">{{ $count }}件</span>
              </div>
            @endforeach
          @else
            <p class="text-muted text-small text-center" style="padding:16px 0">データなし</p>
          @endif
        </div>
      </div>

    </div>{{-- /.tasks panel --}}
  </div>{{-- /.main grid --}}

</div>{{-- /.page-body --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
  var trendData = @json($trend ?? []);

  var labels = trendData.map(function(d) { return d.label || d.date || ''; });
  var values = trendData.map(function(d) { return d.count || d.value || 0; });

  var ctx = document.getElementById('trend-chart');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: '拾得物数',
        data: values,
        borderColor: '#e07b39',
        backgroundColor: 'rgba(224, 123, 57, 0.12)',
        borderWidth: 2.5,
        pointBackgroundColor: '#e07b39',
        pointRadius: 4,
        pointHoverRadius: 6,
        fill: true,
        tension: 0.35
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(ctx) { return ctx.parsed.y + ' 件'; }
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            font: { size: 11 },
            color: '#7a6f63'
          }
        },
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
            font: { size: 11 },
            color: '#7a6f63'
          },
          grid: { color: 'rgba(0,0,0,.06)' }
        }
      }
    }
  });
})();
</script>
@endpush
