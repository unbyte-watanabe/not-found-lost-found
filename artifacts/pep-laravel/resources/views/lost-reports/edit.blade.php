@extends('layouts.app')
@section('title', '紛失届編集')

@section('content')
<div class="page-header">
  <div>
    <h2 class="page-title">紛失届を編集</h2>
    <p class="page-subtitle">登録済みの紛失届情報を修正します</p>
  </div>
  <a href="{{ route('lost-reports.show', $report->id) }}" class="btn btn-secondary">
    <i data-lucide="arrow-left"></i> 詳細へ戻る
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

  <form action="{{ route('lost-reports.update', $report->id) }}" method="POST" novalidate>
    @csrf
    @method('PUT')

    {{-- 届出人情報 --}}
    <div class="card mb-24">
      <div class="card-header">
        <h3 class="card-title">届出人情報</h3>
      </div>
      <div class="card-body">
        <div class="form-grid-2">
          <div class="form-group @error('owner_name') has-error @enderror">
            <label class="form-label" for="owner_name">お名前 <span class="required">*</span></label>
            <input type="text" id="owner_name" name="owner_name" class="form-control"
                   value="{{ old('owner_name', $report->owner_name) }}"
                   required maxlength="255" autocomplete="name">
            @error('owner_name')
              <p class="form-error">{{ $message }}</p>
            @enderror
          </div>

          <div class="form-group @error('owner_contact') has-error @enderror">
            <label class="form-label" for="owner_contact">連絡先（電話またはメール） <span class="required">*</span></label>
            <input type="text" id="owner_contact" name="owner_contact" class="form-control"
                   value="{{ old('owner_contact', $report->owner_contact) }}"
                   required maxlength="255" autocomplete="tel">
            @error('owner_contact')
              <p class="form-error">{{ $message }}</p>
            @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- 紛失物情報 --}}
    <div class="card mb-24">
      <div class="card-header">
        <h3 class="card-title">紛失物の情報</h3>
      </div>
      <div class="card-body">

        <div class="form-group @error('category') has-error @enderror">
          <label class="form-label" for="category">カテゴリ <span class="required">*</span></label>
          <select id="category" name="category" class="form-control" required>
            <option value="">カテゴリを選択</option>
            @foreach(['財布・カバン類','衣類','電子機器','傘','その他'] as $cat)
              <option value="{{ $cat }}" {{ old('category', $report->category) === $cat ? 'selected' : '' }}>
                {{ $cat }}
              </option>
            @endforeach
          </select>
          @error('category')
            <p class="form-error">{{ $message }}</p>
          @enderror
        </div>

        <div class="form-group @error('features') has-error @enderror">
          <label class="form-label" for="features">特徴 <span class="required">*</span></label>
          <textarea id="features" name="features" class="form-control" rows="4"
                    required maxlength="2000"
                    placeholder="色、ブランド、中身の特徴など詳しく記入してください">{{ old('features', $report->features) }}</textarea>
          @error('features')
            <p class="form-error">{{ $message }}</p>
          @enderror
        </div>

        <div class="form-grid-2">
          <div class="form-group @error('lost_datetime_from') has-error @enderror">
            <label class="form-label" for="lost_datetime_from">紛失日時（いつから）</label>
            <input type="datetime-local" id="lost_datetime_from" name="lost_datetime_from" class="form-control"
                   value="{{ old('lost_datetime_from', $report->lost_datetime_from?->format('Y-m-d\TH:i')) }}">
            @error('lost_datetime_from')
              <p class="form-error">{{ $message }}</p>
            @enderror
          </div>

          <div class="form-group @error('lost_datetime_to') has-error @enderror">
            <label class="form-label" for="lost_datetime_to">紛失日時（いつまで）</label>
            <input type="datetime-local" id="lost_datetime_to" name="lost_datetime_to" class="form-control"
                   value="{{ old('lost_datetime_to', $report->lost_datetime_to?->format('Y-m-d\TH:i')) }}">
            @error('lost_datetime_to')
              <p class="form-error">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="form-group @error('lost_location_estimated') has-error @enderror">
          <label class="form-label" for="lost_location_estimated">紛失したと思われる場所</label>
          <input type="text" id="lost_location_estimated" name="lost_location_estimated" class="form-control"
                 value="{{ old('lost_location_estimated', $report->lost_location_estimated) }}"
                 maxlength="255" placeholder="例: 3階フードコート周辺">
          @error('lost_location_estimated')
            <p class="form-error">{{ $message }}</p>
          @enderror
        </div>

      </div>
    </div>

    <div class="form-actions">
      <a href="{{ route('lost-reports.show', $report->id) }}" class="btn btn-secondary">キャンセル</a>
      <button type="submit" class="btn btn-primary">
        <i data-lucide="save"></i> 変更を保存
      </button>
    </div>
  </form>

</div>
@endsection

@push('scripts')
<script src="/js/lost-reports.js"></script>
@endpush
