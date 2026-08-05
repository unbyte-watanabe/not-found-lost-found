<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'オチカン') | オチカン</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/app.css">
  <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js" defer></script>
  @stack('styles')
</head>
<body>

{{-- ===== Mobile-only top bar ===== --}}
<header class="topnav">
  <a class="topnav-logo ochikan-logo" href="{{ route('dashboard') }}">
    オチカン
  </a>
  <button class="topnav-hamburger" data-hamburger aria-label="メニューを開く" aria-expanded="false">
    <i data-lucide="menu" style="width:22px;height:22px;pointer-events:none"></i>
  </button>
</header>

{{-- ===== Sidebar overlay (mobile) ===== --}}
<div class="sidebar-overlay" id="sidebar-overlay"></div>

{{-- ===== App shell ===== --}}
<div class="app-layout">

  {{-- ===== Sidebar (desktop + mobile drawer) ===== --}}
  <aside class="sidebar" role="navigation" aria-label="メインナビゲーション">
    <div class="sidebar-logo">
      <a href="{{ route('dashboard') }}" style="text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:2px;">
        <span class="ochikan-logo" style="font-size:1.15rem;">オチカン</span>
        <span style="font-size:.68rem;color:var(--color-text-muted);">Play Earth Park落とし物管理</span>
      </a>
    </div>

    <nav class="sidebar-nav">
      <a href="{{ route('dashboard') }}"
         class="sidebar-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i>
        ダッシュボード
      </a>
      <a href="{{ route('found-items.index') }}"
         class="sidebar-nav-link {{ request()->routeIs('found-items.*') ? 'active' : '' }}">
        <i data-lucide="package-search"></i>
        拾得物一覧
      </a>
      <a href="{{ route('lost-reports.index') }}"
         class="sidebar-nav-link {{ request()->routeIs('lost-reports.*') ? 'active' : '' }}">
        <i data-lucide="file-search"></i>
        紛失届一覧
      </a>
      <a href="{{ route('matches.index') }}"
         class="sidebar-nav-link {{ request()->routeIs('matches.*') ? 'active' : '' }}">
        <i data-lucide="git-compare-arrows"></i>
        マッチング確認
      </a>
      <a href="{{ route('export.police-form') }}"
         class="sidebar-nav-link {{ request()->routeIs('export.*') ? 'active' : '' }}">
        <i data-lucide="printer"></i>
        警察提出出力
      </a>
    </nav>

    <div class="sidebar-footer" style="font-size:.7rem;color:var(--color-text-muted);">
      <div>© {{ date('Y') }} Play Earth Park</div>
    </div>
  </aside>

  {{-- ===== Main content ===== --}}
  <main class="main-content" id="main-content">
    @yield('content')
  </main>

</div>{{-- /.app-layout --}}

{{-- ===== Mobile bottom navigation ===== --}}
<nav class="bottom-nav" aria-label="モバイルナビゲーション">
  <div class="bottom-nav-inner">
    <a href="{{ route('dashboard') }}"
       class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <i data-lucide="layout-dashboard"></i>
      <span>ホーム</span>
    </a>
    <a href="{{ route('found-items.index') }}"
       class="bottom-nav-item {{ request()->routeIs('found-items.*') ? 'active' : '' }}">
      <i data-lucide="package-search"></i>
      <span>拾得物</span>
    </a>
    <a href="{{ route('lost-reports.index') }}"
       class="bottom-nav-item {{ request()->routeIs('lost-reports.*') ? 'active' : '' }}">
      <i data-lucide="file-search"></i>
      <span>紛失届</span>
    </a>
    <a href="{{ route('matches.index') }}"
       class="bottom-nav-item {{ request()->routeIs('matches.*') ? 'active' : '' }}">
      <i data-lucide="git-compare-arrows"></i>
      <span>照合</span>
    </a>
    <a href="{{ route('export.police-form') }}"
       class="bottom-nav-item {{ request()->routeIs('export.*') ? 'active' : '' }}">
      <i data-lucide="printer"></i>
      <span>出力</span>
    </a>
  </div>
</nav>

{{-- ===== Toast container ===== --}}
<div class="toast-container" id="toast-container" aria-live="polite" aria-atomic="false">
  @if(session('success'))
    <div class="toast success" role="alert">
      <span class="toast-icon success"><i data-lucide="check-circle" style="width:18px;height:18px"></i></span>
      <span class="toast-body">{{ session('success') }}</span>
      <button class="toast-close" aria-label="閉じる"><i data-lucide="x" style="width:14px;height:14px"></i></button>
    </div>
  @endif
  @if(session('error'))
    <div class="toast error" role="alert">
      <span class="toast-icon error"><i data-lucide="alert-circle" style="width:18px;height:18px"></i></span>
      <span class="toast-body">{{ session('error') }}</span>
      <button class="toast-close" aria-label="閉じる"><i data-lucide="x" style="width:14px;height:14px"></i></button>
    </div>
  @endif
</div>

<script src="/js/app.js"></script>
@stack('scripts')
</body>
</html>
