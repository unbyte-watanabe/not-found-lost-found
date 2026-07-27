@extends('layouts.app')
@section('title', '紛失届登録')

@section('content')
<div class="page-header">
  <div>
    <h2 class="page-title">紛失届を登録</h2>
    <p class="page-subtitle">紛失物の情報と所有者の情報を入力してください</p>
  </div>
  <a href="{{ route('lost-reports.index') }}" class="btn btn-secondary">
    <i data-lucide="arrow-left"></i> 一覧へ戻る
  </a>
</div>

<div class="page-body">

  @if($errors->any())
  <div class="alert alert-danger" role="alert">
    <i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0"></i>
    <div>
      <strong>入力エラーがあります</strong>
      <ul style="margin:4px 0 0 16px; font-size:.82rem;">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  <form method="POST" action="{{ route('lost-reports.store') }}" id="create-report-form" novalidate>
    @csrf

    <div style="display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start;">

      {{-- ===== Left: main form ===== --}}
      <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Lost item details --}}
        <div class="card">
          <div class="card-header">
            <span class="card-title">
              <i data-lucide="search" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
              紛失物情報
            </span>
          </div>
          <div class="card-body">

            <div class="form-group">
              <label class="form-label" for="category">
                カテゴリ <span class="req">*</span>
              </label>
              <select name="category" id="category"
                      class="form-control {{ $errors->has('category') ? 'is-invalid' : '' }}"
                      required>
                <option value="">選択してください</option>
                @foreach(['財布・カバン類','衣類','電子機器','傘','その他'] as $cat)
                  <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                    {{ $cat }}
                  </option>
                @endforeach
              </select>
              @error('category')
                <p class="form-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="form-group">
              <label class="form-label" for="features">
                特徴・外観 <span class="req">*</span>
              </label>
              <textarea name="features" id="features" rows="5"
                        class="form-control {{ $errors->has('features') ? 'is-invalid' : '' }}"
                        placeholder="色、ブランド、サイズ、特徴的な点など詳細に記入してください&#10;例：黒色の長財布、内側に家族写真あり、ブランドはLOUIS VUITTON"
                        required>{{ old('features') }}</textarea>
              <p class="form-hint">できるだけ詳しく記載することで、マッチング精度が向上します</p>
              @error('features')
                <p class="form-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="form-group">
              <label class="form-label" for="lost_location_estimated">紛失場所（推定）</label>
              <input type="text" name="lost_location_estimated" id="lost_location_estimated"
                     class="form-control {{ $errors->has('lost_location_estimated') ? 'is-invalid' : '' }}"
                     value="{{ old('lost_location_estimated') }}"
                     placeholder="例：メインゲート付近、フードコート">
              @error('lost_location_estimated')
                <p class="form-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="grid-2">
              <div class="form-group">
                <label class="form-label" for="lost_datetime_from">紛失日時（From）</label>
                <input type="datetime-local" name="lost_datetime_from" id="lost_datetime_from"
                       class="form-control {{ $errors->has('lost_datetime_from') ? 'is-invalid' : '' }}"
                       value="{{ old('lost_datetime_from') }}">
                <p class="form-hint">紛失した最も早い日時</p>
                @error('lost_datetime_from')
                  <p class="form-error">{{ $message }}</p>
                @enderror
              </div>

              <div class="form-group">
                <label class="form-label" for="lost_datetime_to">紛失日時（To）</label>
                <input type="datetime-local" name="lost_datetime_to" id="lost_datetime_to"
                       class="form-control {{ $errors->has('lost_datetime_to') ? 'is-invalid' : '' }}"
                       value="{{ old('lost_datetime_to') }}">
                <p class="form-hint">紛失した最も遅い日時</p>
                @error('lost_datetime_to')
                  <p class="form-error">{{ $message }}</p>
                @enderror
              </div>
            </div>

          </div>
        </div>

      </div>{{-- /.left --}}

      {{-- ===== Right: owner info ===== --}}
      <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Owner info --}}
        <div class="card">
          <div class="card-header">
            <span class="card-title">
              <i data-lucide="user" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
              お客様情報
            </span>
          </div>
          <div class="card-body">

            <div class="form-group">
              <label class="form-label" for="owner_name">
                お名前 <span class="req">*</span>
              </label>
              <input type="text" name="owner_name" id="owner_name"
                     class="form-control {{ $errors->has('owner_name') ? 'is-invalid' : '' }}"
                     value="{{ old('owner_name') }}"
                     placeholder="山田 太郎" required>
              @error('owner_name')
                <p class="form-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="form-group">
              <label class="form-label" for="owner_contact">
                連絡先 <span class="req">*</span>
              </label>
              <input type="text" name="owner_contact" id="owner_contact"
                     class="form-control {{ $errors->has('owner_contact') ? 'is-invalid' : '' }}"
                     value="{{ old('owner_contact') }}"
                     placeholder="090-0000-0000 または example@email.com"
                     required>
              <p class="form-hint">見つかった際の連絡先（電話・メール）</p>
              @error('owner_contact')
                <p class="form-error">{{ $message }}</p>
              @enderror
            </div>

          </div>
        </div>

        {{-- Notice --}}
        <div class="card" style="background:var(--blue-bg); border-color:#a0bde8">
          <div class="card-body" style="padding:16px">
            <p style="font-size:.8rem; font-weight:600; color:var(--blue); margin-bottom:8px;">
              <i data-lucide="info" style="width:14px;height:14px;vertical-align:middle;margin-right:4px"></i>
              個人情報の取り扱いについて
            </p>
            <p style="font-size:.75rem; color:var(--text-muted); line-height:1.6;">
              ご入力いただいた個人情報は、紛失物の照合・連絡のみに使用します。第三者への提供は行いません。
            </p>
          </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-8" style="flex-direction:column">
          <button type="submit" class="btn btn-primary btn-lg w-full" style="justify-content:center">
            <i data-lucide="save"></i> 登録する
          </button>
          <a href="{{ route('lost-reports.index') }}" class="btn btn-secondary w-full" style="justify-content:center">
            キャンセル
          </a>
        </div>

      </div>{{-- /.right --}}
    </div>{{-- /.grid --}}

  </form>
</div>{{-- /.page-body --}}
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Validate that lost_datetime_to is >= lost_datetime_from
  var fromEl = document.getElementById('lost_datetime_from');
  var toEl   = document.getElementById('lost_datetime_to');

  if (fromEl && toEl) {
    toEl.addEventListener('change', function () {
      if (fromEl.value && toEl.value && toEl.value < fromEl.value) {
        PEP.showToast('「紛失日時To」は「From」より後の日時を指定してください', 'error');
        toEl.value = '';
      }
    });
  }
});
</script>
@endpush
