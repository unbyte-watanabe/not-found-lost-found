@extends('layouts.app')
@section('title', '警察提出データ出力')

@section('content')
<div class="page-header">
  <div>
    <h2 class="page-title">警察提出データ出力</h2>
    <p class="page-subtitle">拾得物法に基づく警察提出用データのCSVダウンロード</p>
  </div>
</div>

<div class="page-body">

  <div style="display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start;">

    {{-- ===== Left: main form ===== --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

      {{-- Download form --}}
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="download" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            CSVダウンロード
          </span>
        </div>
        <div class="card-body">

          <p style="font-size:.85rem; color:var(--text-muted); margin-bottom:20px; line-height:1.7;">
            出力する拾得物の期間を指定してください。指定期間内に拾得された、
            <strong>警察提出済み</strong>または<strong>保管中</strong>のデータを出力します。
          </p>

          <form method="GET" action="{{ route('export.police-csv') }}" id="export-form" novalidate>

            <div class="grid-2">
              <div class="form-group">
                <label class="form-label" for="date_from">
                  開始日 <span class="req">*</span>
                </label>
                <input type="date" name="date_from" id="date_from"
                       class="form-control {{ $errors->has('date_from') ? 'is-invalid' : '' }}"
                       value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}"
                       required>
                @error('date_from')
                  <p class="form-error">{{ $message }}</p>
                @enderror
              </div>

              <div class="form-group">
                <label class="form-label" for="date_to">
                  終了日 <span class="req">*</span>
                </label>
                <input type="date" name="date_to" id="date_to"
                       class="form-control {{ $errors->has('date_to') ? 'is-invalid' : '' }}"
                       value="{{ request('date_to', now()->format('Y-m-d')) }}"
                       required>
                @error('date_to')
                  <p class="form-error">{{ $message }}</p>
                @enderror
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">出力対象ステータス</label>
              <div class="flex gap-16 flex-wrap" style="margin-top:4px">
                <label class="form-check">
                  <input type="checkbox" name="statuses[]" value="保管中"
                         class="form-check-input" checked>
                  <span>保管中</span>
                </label>
                <label class="form-check">
                  <input type="checkbox" name="statuses[]" value="警察提出済"
                         class="form-check-input" checked>
                  <span>警察提出済</span>
                </label>
                <label class="form-check">
                  <input type="checkbox" name="statuses[]" value="返還済"
                         class="form-check-input">
                  <span>返還済</span>
                </label>
                <label class="form-check">
                  <input type="checkbox" name="statuses[]" value="期間満了処分"
                         class="form-check-input">
                  <span>期間満了処分</span>
                </label>
              </div>
            </div>

            <div style="margin-top:20px">
              <button type="submit" class="btn btn-primary btn-lg" id="download-btn">
                <i data-lucide="download"></i> CSVダウンロード
              </button>
            </div>

          </form>

        </div>
      </div>

      {{-- CSV format info --}}
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="table" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            CSV出力フォーマット
          </span>
        </div>
        <div class="card-body">

          <p class="text-small text-muted" style="margin-bottom:16px">
            出力されるCSVファイルの列は以下の通りです。文字コードはUTF-8（BOM付き）で出力されます。
          </p>

          <div class="table-wrap" style="box-shadow:none; border-radius:var(--radius)">
            <table class="table" style="font-size:.78rem">
              <thead>
                <tr>
                  <th style="width:40px">#</th>
                  <th>列名</th>
                  <th>内容</th>
                  <th>例</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $columns = [
                    ['管理番号',       '拾得物の管理番号',             '20240115-0001'],
                    ['拾得日時',       '拾得された日時',               '2024/01/15 14:30'],
                    ['カテゴリ',       '拾得物のカテゴリ',             '財布・カバン類'],
                    ['サブカテゴリ',   '拾得物の詳細分類',             '長財布'],
                    ['特徴',           '外観・特徴の説明',             '黒色、ブランドロゴあり'],
                    ['拾得場所',       '拾得された場所',               'エントランス付近'],
                    ['保管場所',       '現在の保管場所',               '事務所ロッカーA'],
                    ['拾得者氏名',     '拾得者のお名前',               '田中 一郎'],
                    ['権利放棄',       '権利放棄の有無',               '済/未'],
                    ['ステータス',     '現在のステータス',             '保管中'],
                    ['登録日時',       'システムへの登録日時',         '2024/01/15 14:35'],
                  ];
                @endphp
                @foreach($columns as $i => $col)
                <tr>
                  <td class="text-muted">{{ $i + 1 }}</td>
                  <td style="font-weight:600">{{ $col[0] }}</td>
                  <td class="text-muted">{{ $col[1] }}</td>
                  <td class="font-mono" style="color:var(--accent); font-size:.73rem">{{ $col[2] }}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

        </div>
      </div>

    </div>{{-- /.left --}}

    {{-- ===== Right: info panel ===== --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

      {{-- Notice --}}
      <div class="card" style="background:var(--yellow-bg); border-color:#e6d080">
        <div class="card-body" style="padding:18px">
          <p style="font-size:.85rem; font-weight:600; color:var(--yellow); margin-bottom:10px;">
            <i data-lucide="alert-triangle" style="width:15px;height:15px;vertical-align:middle;margin-right:5px"></i>
            警察提出に関するご注意
          </p>
          <ul style="font-size:.78rem; color:var(--text-muted); margin-left:16px; line-height:1.9;">
            <li>拾得物法により、拾得から7日以内に警察への届け出が必要です</li>
            <li>提出前に必ず内容の確認を行ってください</li>
            <li>CSVダウンロード後、提出済みの場合はステータスを「警察提出済」に更新してください</li>
            <li>個人情報の取り扱いに十分注意してください</li>
          </ul>
        </div>
      </div>

      {{-- Quick stats --}}
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="bar-chart-2" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            現在の件数
          </span>
        </div>
        <div class="card-body" style="padding:0">
          <ul class="task-list" style="padding:0 20px">
            <li>
              <span class="flex items-center gap-8">
                <span style="width:8px;height:8px;border-radius:50%;background:var(--green);flex-shrink:0;display:inline-block"></span>
                保管中
              </span>
              <span class="badge badge-green">{{ $statusCounts['保管中'] ?? '—' }}件</span>
            </li>
            <li>
              <span class="flex items-center gap-8">
                <span style="width:8px;height:8px;border-radius:50%;background:var(--gray);flex-shrink:0;display:inline-block"></span>
                警察提出済
              </span>
              <span class="badge badge-gray">{{ $statusCounts['警察提出済'] ?? '—' }}件</span>
            </li>
            <li>
              <span class="flex items-center gap-8">
                <span style="width:8px;height:8px;border-radius:50%;background:var(--blue);flex-shrink:0;display:inline-block"></span>
                返還済
              </span>
              <span class="badge badge-blue">{{ $statusCounts['返還済'] ?? '—' }}件</span>
            </li>
            <li>
              <span class="flex items-center gap-8">
                <span style="width:8px;height:8px;border-radius:50%;background:var(--red);flex-shrink:0;display:inline-block"></span>
                期間満了処分
              </span>
              <span class="badge badge-red">{{ $statusCounts['期間満了処分'] ?? '—' }}件</span>
            </li>
          </ul>
        </div>
      </div>

      {{-- Period near-expiry alert --}}
      @if(($nearExpiryCount ?? 0) > 0)
      <div class="alert alert-danger" role="alert" style="margin-bottom:0">
        <i data-lucide="clock-alert" style="width:16px;height:16px;flex-shrink:0"></i>
        <div>
          <strong>{{ $nearExpiryCount }}件</strong>の拾得物が7日以内に保管期限を迎えます。
          <a href="{{ route('found-items.index') }}?near_expiry=1" style="color:var(--red); font-weight:600">
            確認する →
          </a>
        </div>
      </div>
      @endif

    </div>{{-- /.right --}}
  </div>{{-- /.grid --}}

</div>{{-- /.page-body --}}
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var form     = document.getElementById('export-form');
  var fromEl   = document.getElementById('date_from');
  var toEl     = document.getElementById('date_to');
  var btn      = document.getElementById('download-btn');

  if (form) {
    form.addEventListener('submit', function (e) {
      if (!fromEl.value || !toEl.value) {
        e.preventDefault();
        PEP.showToast('開始日と終了日を入力してください', 'error');
        return;
      }
      if (toEl.value < fromEl.value) {
        e.preventDefault();
        PEP.showToast('終了日は開始日以降の日付を指定してください', 'error');
        return;
      }
      // Visual feedback
      if (btn) {
        btn.innerHTML = '<span class="spinner"></span> ダウンロード中...';
        btn.disabled = true;
        setTimeout(function () {
          btn.innerHTML = '<i data-lucide="download"></i> CSVダウンロード';
          btn.disabled = false;
          if (window.lucide) lucide.createIcons();
        }, 3000);
      }
    });
  }
});
</script>
@endpush
