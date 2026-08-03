@extends('layouts.app')
@section('title', '拾得物詳細 - ' . $item->management_no)

@section('content')
<div class="page-header">
  <div>
    <h2 class="page-title">
      拾得物詳細
      <x-status-badge :status="$item->status" type="found" />
    </h2>
    <p class="page-subtitle font-mono">{{ $item->management_no }}</p>
  </div>
  <div class="flex gap-8 flex-wrap">
    <a href="{{ route('found-items.edit', $item->id) }}" class="btn btn-secondary">
      <i data-lucide="edit-2"></i> 編集
    </a>
    <a href="{{ route('found-items.index') }}" class="btn btn-secondary">
      <i data-lucide="arrow-left"></i> 一覧へ戻る
    </a>
  </div>
</div>

<div class="page-body">

  {{-- Near expiry alert --}}
  @if($item->nearing_expiry ?? false)
  <div class="alert alert-warning" role="alert">
    <i data-lucide="clock-alert" style="width:16px;height:16px;flex-shrink:0"></i>
    <span>この拾得物は保管期限が近づいています。警察提出を検討してください。</span>
  </div>
  @endif

  <div class="detail-layout">

    {{-- ===== Left: main details ===== --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

      {{-- Basic info card --}}
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="info" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            基本情報
          </span>
        </div>
        <div class="card-body">
          <div class="detail-grid">
            <div class="detail-field">
              <label>管理番号</label>
              <p class="mono">{{ $item->management_no }}</p>
            </div>
            <div class="detail-field">
              <label>ステータス</label>
              <p><x-status-badge :status="$item->status" type="found" /></p>
            </div>
            <div class="detail-field">
              <label>カテゴリ</label>
              <p>{{ $item->category }}</p>
            </div>
            <div class="detail-field">
              <label>サブカテゴリ</label>
              <p>{{ $item->sub_category ?: '—' }}</p>
            </div>
            <div class="detail-field" style="grid-column:span 2">
              <label>特徴・外観</label>
              <p style="white-space:pre-wrap">{{ $item->features }}</p>
            </div>
            <div class="detail-field">
              <label>拾得日時</label>
              <p>{{ \Carbon\Carbon::parse($item->found_datetime)->format('Y年m月d日 H:i') }}</p>
            </div>
            <div class="detail-field">
              <label>拾得場所</label>
              <p>{{ $item->found_location ?: '—' }}</p>
            </div>
            <div class="detail-field">
              <label>保管場所</label>
              <p>{{ $item->storage_location ?: '—' }}</p>
            </div>
            <div class="detail-field">
              <label>登録日時</label>
              <p class="text-muted text-small">{{ \Carbon\Carbon::parse($item->created_at)->format('Y/m/d H:i') }}</p>
            </div>
          </div>
        </div>
      </div>

      {{-- Finder info card --}}
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="user" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            拾得者情報
          </span>
        </div>
        <div class="card-body">
          <div class="detail-grid">
            <div class="detail-field">
              <label>拾得者氏名</label>
              <p>{{ $item->finder_name ?: '—' }}</p>
            </div>
            <div class="detail-field">
              <label>拾得者連絡先</label>
              <p>{{ $item->finder_contact ?: '—' }}</p>
            </div>
            <div class="detail-field">
              <label>権利放棄</label>
              <p>
                @if($item->rights_waived)
                  <span class="badge badge-green">放棄済み</span>
                @else
                  <span class="badge badge-gray">未放棄</span>
                @endif
              </p>
            </div>
          </div>
        </div>
      </div>

      {{-- Return info card (shown if returned) --}}
      @if(in_array($item->status, ['返還済']))
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="handshake" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            返還情報
          </span>
        </div>
        <div class="card-body">
          <div class="detail-grid">
            <div class="detail-field">
              <label>返還日時</label>
              <p>{{ $item->returned_at ? \Carbon\Carbon::parse($item->returned_at)->format('Y年m月d日 H:i') : '—' }}</p>
            </div>
            <div class="detail-field">
              <label>返還先（受取人）</label>
              <p>{{ $item->returned_to ?: '—' }}</p>
            </div>
            <div class="detail-field">
              <label>担当者</label>
              <p>{{ $item->returned_by ?: '—' }}</p>
            </div>
            <div class="detail-field">
              <label>本人確認</label>
              <p>
                @if($item->identity_verified)
                  <span class="badge badge-green">確認済み</span>
                @else
                  <span class="badge badge-gray">未確認</span>
                @endif
              </p>
            </div>
            <div class="detail-field">
              <label>受取書</label>
              <p>
                @if($item->receipt_signed)
                  <span class="badge badge-green">署名済み</span>
                @else
                  <span class="badge badge-gray">未署名</span>
                @endif
              </p>
            </div>
          </div>
        </div>
      </div>
      @endif

      {{-- ===== Return processing section (status=保管中 only) ===== --}}
      @if($item->status === '保管中')
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="settings" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            ステータス変更
          </span>
        </div>
        <div class="card-body">
          <div class="flex gap-8 flex-wrap">
            <button type="button" class="btn btn-success" id="btn-return-open">
              <i data-lucide="handshake"></i> 返還処理
            </button>
            <form method="POST" action="{{ route('found-items.status', $item->id) }}" style="display:inline"
                  id="police-form">
              @csrf
              @method('PATCH')
              <input type="hidden" name="status" value="警察提出済">
              <button type="submit" class="btn btn-secondary"
                      data-confirm="警察提出済みに変更しますか？この操作は取り消せません。">
                <i data-lucide="shield"></i> 警察提出
              </button>
            </form>
            <form method="POST" action="{{ route('found-items.status', $item->id) }}" style="display:inline"
                  id="expire-form">
              @csrf
              @method('PATCH')
              <input type="hidden" name="status" value="期間満了処分">
              <button type="submit" class="btn btn-danger"
                      data-confirm="期間満了処分に変更しますか？この操作は取り消せません。">
                <i data-lucide="trash-2"></i> 期間満了処分
              </button>
            </form>
          </div>

          {{-- Inline return form --}}
          <div class="inline-form-wrap" id="return-form-wrap">
            <form method="POST" action="{{ route('found-items.status', $item->id) }}" id="return-form">
              @csrf
              @method('PATCH')
              <input type="hidden" name="status" value="返還済">

              <h4 style="font-size:.88rem; font-weight:600; margin-bottom:14px; color:var(--text);">返還処理入力</h4>

              <div class="grid-2">
                <div class="form-group">
                  <label class="form-label" for="returned_to">
                    受取人氏名 <span class="req">*</span>
                  </label>
                  <input type="text" name="returned_to" id="returned_to"
                         class="form-control" required placeholder="受取人のお名前">
                </div>
                <div class="form-group">
                  <label class="form-label" for="returned_by">
                    担当者 <span class="req">*</span>
                  </label>
                  <input type="text" name="returned_by" id="returned_by"
                         class="form-control" required placeholder="対応スタッフ名">
                </div>
              </div>

              <div class="flex gap-16 flex-wrap" style="margin-top:4px">
                <label class="form-check">
                  <input type="checkbox" name="identity_verified" class="form-check-input" value="1">
                  <span>本人確認済み</span>
                </label>
                <label class="form-check">
                  <input type="checkbox" name="receipt_signed" class="form-check-input" value="1">
                  <span>受取書署名済み</span>
                </label>
              </div>

              <div class="flex gap-8 mt-16">
                <button type="submit" class="btn btn-success">
                  <i data-lucide="check"></i> 返還を確定する
                </button>
                <button type="button" class="btn btn-secondary" id="btn-return-cancel">
                  キャンセル
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
      @endif

    </div>{{-- /.left --}}

    {{-- ===== Right: image + actions ===== --}}
    <div style="display:flex; flex-direction:column; gap:16px;">

      {{-- Image --}}
      @if($item->image_url)
      <div class="card">
        <div class="card-header">
          <span class="card-title">
            <i data-lucide="image" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            画像
          </span>
        </div>
        <div class="card-body" style="padding:12px">
          <a href="{{ $item->image_url }}" target="_blank" rel="noopener noreferrer">
            <img src="{{ $item->image_url }}" alt="拾得物の画像"
                 style="width:100%; border-radius:var(--radius); object-fit:cover; max-height:280px;">
          </a>
          <p class="text-small text-muted" style="margin-top:6px; text-align:center">クリックで拡大表示</p>
        </div>
      </div>
      @else
      <div class="card">
        <div class="card-body text-center" style="padding:32px 16px; color:var(--text-light)">
          <i data-lucide="image-off" style="width:36px;height:36px; margin:0 auto 8px"></i>
          <p class="text-small">画像なし</p>
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
          <a href="{{ route('matches.index') }}?found_item_id={{ $item->id }}"
             class="btn btn-secondary w-full" style="justify-content:center">
            <i data-lucide="search"></i> マッチング候補を確認
          </a>
        </div>
      </div>

      {{-- Danger zone --}}
      <div class="card" style="border-color:var(--red)">
        <div class="card-header" style="border-bottom-color:var(--red)">
          <span class="card-title" style="color:var(--red)">
            <i data-lucide="trash-2" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
            削除
          </span>
        </div>
        <div class="card-body">
          <p class="text-small text-muted mb-16">削除すると元に戻せません。</p>
          <form method="POST" action="{{ route('found-items.destroy', $item->id) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger w-full" style="justify-content:center"
                    data-confirm="この拾得物を削除しますか？この操作は取り消せません。">
              <i data-lucide="trash-2"></i> 削除する
            </button>
          </form>
        </div>
      </div>

    </div>{{-- /.right --}}
  </div>{{-- /.grid --}}

</div>{{-- /.page-body --}}
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var openBtn   = document.getElementById('btn-return-open');
  var cancelBtn = document.getElementById('btn-return-cancel');
  var formWrap  = document.getElementById('return-form-wrap');

  if (openBtn && formWrap) {
    openBtn.addEventListener('click', function () {
      formWrap.classList.toggle('open');
      openBtn.textContent = formWrap.classList.contains('open') ? '▲ 返還フォームを閉じる' : '▼ 返還処理';
    });
  }
  if (cancelBtn && formWrap) {
    cancelBtn.addEventListener('click', function () {
      formWrap.classList.remove('open');
      if (openBtn) openBtn.textContent = '▼ 返還処理';
    });
  }
});
</script>
@endpush
