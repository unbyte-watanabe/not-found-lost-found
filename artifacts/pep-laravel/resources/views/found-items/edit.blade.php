@extends('layouts.app')
@section('title', '拾得物編集 - ' . $item->management_no)

@section('content')
<div class="page-header">
  <div>
    <h2 class="page-title">拾得物を編集</h2>
    <p class="page-subtitle font-mono">{{ $item->management_no }}</p>
  </div>
  <div class="flex gap-8">
    <a href="{{ route('found-items.show', $item->id) }}" class="btn btn-secondary">
      <i data-lucide="arrow-left"></i> 詳細へ戻る
    </a>
  </div>
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

  <form method="POST" action="{{ route('found-items.update', $item->id) }}"
        enctype="multipart/form-data" id="edit-form" novalidate>
    @csrf
    @method('PUT')

    <div style="display:grid; grid-template-columns:1fr 360px; gap:20px; align-items:start;">

      {{-- ===== Left column: main form ===== --}}
      <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Image card --}}
        <div class="card">
          <div class="card-header">
            <span class="card-title">
              <i data-lucide="image" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
              画像
            </span>
          </div>
          <div class="card-body" style="position:relative">

            {{-- Current image preview --}}
            @if($item->image_url)
            <div id="upload-preview" class="upload-preview" style="display:block; margin-bottom:12px">
              <img src="{{ $item->image_url }}" alt="現在の画像" id="preview-img"
                   style="max-width:100%; max-height:220px; border-radius:var(--radius); object-fit:contain; border:1px solid var(--border); margin:0 auto">
            </div>
            @else
            <div class="upload-preview" id="upload-preview">
              <img src="" alt="プレビュー" id="preview-img">
            </div>
            @endif

            <div class="upload-zone" id="upload-zone" role="button" tabindex="0"
                 aria-label="新しい画像をアップロード">
              <div class="upload-zone-icon">
                <i data-lucide="upload-cloud" style="width:40px;height:40px"></i>
              </div>
              <div class="upload-zone-label">
                {{ $item->image_url ? '画像を差し替える（クリックまたはドラッグ＆ドロップ）' : 'クリックまたはドラッグ＆ドロップで画像をアップロード' }}
              </div>
              <div class="upload-zone-hint">JPEG, PNG, WebP / 最大10MB</div>
              <input type="file" id="image-file-input" name="image_file"
                     accept="image/jpeg,image/png,image/webp,image/gif"
                     style="display:none" aria-hidden="true">
            </div>

            <div style="margin-top:12px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
              <span id="upload-spinner" class="spinner" style="display:none" aria-label="アップロード中"></span>
              <button type="button" class="btn btn-secondary" id="ai-analyze-btn"
                      style="{{ $item->image_url ? 'display:inline-flex' : 'display:none' }}"
                      data-image-url="{{ $item->image_url }}">
                <i data-lucide="sparkles"></i> AIで自動入力
              </button>
              <span id="upload-status" class="text-small text-muted" aria-live="polite"></span>
            </div>

            <input type="hidden" name="image_url" id="image_url"
                   value="{{ old('image_url', $item->image_url) }}">
          </div>
        </div>

        {{-- Item details card --}}
        <div class="card">
          <div class="card-header">
            <span class="card-title">
              <i data-lucide="tag" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
              拾得物情報
            </span>
          </div>
          <div class="card-body">

            <div class="grid-2">
              <div class="form-group">
                <label class="form-label" for="category">
                  カテゴリ <span class="req">*</span>
                </label>
                <select name="category" id="category"
                        class="form-control {{ $errors->has('category') ? 'is-invalid' : '' }}"
                        required>
                  <option value="">選択してください</option>
                  @foreach(['財布・カバン類','衣類','電子機器','傘','その他'] as $cat)
                    <option value="{{ $cat }}"
                      {{ old('category', $item->category) === $cat ? 'selected' : '' }}>
                      {{ $cat }}
                    </option>
                  @endforeach
                </select>
                @error('category')
                  <p class="form-error">{{ $message }}</p>
                @enderror
              </div>

              <div class="form-group">
                <label class="form-label" for="sub_category">サブカテゴリ</label>
                <input type="text" name="sub_category" id="sub_category"
                       class="form-control {{ $errors->has('sub_category') ? 'is-invalid' : '' }}"
                       value="{{ old('sub_category', $item->sub_category) }}"
                       placeholder="例：長財布、折り畳み傘">
                @error('sub_category')
                  <p class="form-error">{{ $message }}</p>
                @enderror
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="features">
                特徴・外観 <span class="req">*</span>
              </label>
              <textarea name="features" id="features" rows="4"
                        class="form-control {{ $errors->has('features') ? 'is-invalid' : '' }}"
                        placeholder="色、ブランド、サイズ、特徴的な点など詳細に記入してください"
                        required>{{ old('features', $item->features) }}</textarea>
              @error('features')
                <p class="form-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="grid-2">
              <div class="form-group">
                <label class="form-label" for="found_datetime">
                  拾得日時 <span class="req">*</span>
                </label>
                <input type="datetime-local" name="found_datetime" id="found_datetime"
                       class="form-control {{ $errors->has('found_datetime') ? 'is-invalid' : '' }}"
                       value="{{ old('found_datetime', \Carbon\Carbon::parse($item->found_datetime)->format('Y-m-d\TH:i')) }}"
                       required>
                @error('found_datetime')
                  <p class="form-error">{{ $message }}</p>
                @enderror
              </div>

              <div class="form-group">
                <label class="form-label" for="found_location">拾得場所</label>
                <input type="text" name="found_location" id="found_location"
                       class="form-control {{ $errors->has('found_location') ? 'is-invalid' : '' }}"
                       value="{{ old('found_location', $item->found_location) }}"
                       placeholder="例：エントランス付近">
                @error('found_location')
                  <p class="form-error">{{ $message }}</p>
                @enderror
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="storage_location">保管場所</label>
              <input type="text" name="storage_location" id="storage_location"
                     class="form-control {{ $errors->has('storage_location') ? 'is-invalid' : '' }}"
                     value="{{ old('storage_location', $item->storage_location) }}"
                     placeholder="例：事務所ロッカーA">
              @error('storage_location')
                <p class="form-error">{{ $message }}</p>
              @enderror
            </div>

            {{-- Status (editable) --}}
            <div class="form-group">
              <label class="form-label" for="status">ステータス</label>
              <select name="status" id="status"
                      class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}">
                @foreach(['保管中','返還済','警察提出済','期間満了処分'] as $s)
                  <option value="{{ $s }}"
                    {{ old('status', $item->status) === $s ? 'selected' : '' }}>
                    {{ $s }}
                  </option>
                @endforeach
              </select>
              @error('status')
                <p class="form-error">{{ $message }}</p>
              @enderror
            </div>

          </div>
        </div>

      </div>{{-- /.left --}}

      {{-- ===== Right column ===== --}}
      <div style="display:flex; flex-direction:column; gap:16px;">

        {{-- Finder info --}}
        <div class="card">
          <div class="card-header">
            <span class="card-title">
              <i data-lucide="user" style="width:16px;height:16px;vertical-align:middle;margin-right:6px"></i>
              拾得者情報
            </span>
          </div>
          <div class="card-body">
            <div class="form-group">
              <label class="form-label" for="finder_name">拾得者氏名</label>
              <input type="text" name="finder_name" id="finder_name"
                     class="form-control {{ $errors->has('finder_name') ? 'is-invalid' : '' }}"
                     value="{{ old('finder_name', $item->finder_name) }}"
                     placeholder="任意">
              @error('finder_name')
                <p class="form-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="form-group">
              <label class="form-label" for="finder_contact">拾得者連絡先</label>
              <input type="text" name="finder_contact" id="finder_contact"
                     class="form-control {{ $errors->has('finder_contact') ? 'is-invalid' : '' }}"
                     value="{{ old('finder_contact', $item->finder_contact) }}"
                     placeholder="電話番号またはメールアドレス">
              @error('finder_contact')
                <p class="form-error">{{ $message }}</p>
              @enderror
            </div>

            <div class="form-group">
              <label class="form-check">
                <input type="checkbox" name="rights_waived" class="form-check-input"
                       value="1" {{ old('rights_waived', $item->rights_waived) ? 'checked' : '' }}>
                <span>権利放棄済み</span>
              </label>
            </div>
          </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-8" style="flex-direction:column">
          <button type="submit" class="btn btn-primary btn-lg w-full" style="justify-content:center">
            <i data-lucide="save"></i> 変更を保存する
          </button>
          <a href="{{ route('found-items.show', $item->id) }}"
             class="btn btn-secondary w-full" style="justify-content:center">
            キャンセル
          </a>
        </div>

        {{-- Last updated --}}
        <p class="text-small text-muted text-center">
          最終更新: {{ \Carbon\Carbon::parse($item->updated_at)->format('Y/m/d H:i') }}
        </p>

      </div>{{-- /.right --}}
    </div>{{-- /.grid --}}

  </form>
</div>{{-- /.page-body --}}
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  PEP.initUploadZone('upload-zone', 'image-file-input', 'upload-preview', function(data) {
    var status = document.getElementById('upload-status');
    if (status && data.url) status.textContent = '✓ アップロード完了';
  });
  PEP.initAiAnalyze();

  var zone = document.getElementById('upload-zone');
  if (zone) {
    zone.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        document.getElementById('image-file-input').click();
      }
    });
  }
});
</script>
@endpush
