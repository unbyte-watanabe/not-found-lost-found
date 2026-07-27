<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'PEP落とし物管理') | PEP落とし物管理</title>
  <link rel="stylesheet" href="/css/app.css">
  {{-- Lucide icons --}}
  <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js" defer></script>
  @stack('styles')
</head>
<body>

{{-- ===== Mobile top nav ===== --}}
<nav class="topnav">
  <span class="topnav-title">🧸 PEP落とし物管理</span>
  <button class="hamburger" id="hamburger-btn" aria-label="メニューを開く">
    <i data-lucide="menu" style="width:22px;height:22px"></i>
  </button>
</nav>

{{-- ===== Mobile slide-in menu ===== --}}
<div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="ナビゲーションメニュー">
  <div class="mobile-overlay" id="mobile-overlay"></div>
  <div class="mobile-panel">
    <button class="mobile-close" id="mobile-close" aria-label="メニューを閉じる">
      <i data-lucide="x" style="width:20px;height:20px"></i>
    </button>
    <div style="padding:20px 0 0">
      <div style="padding:0 20px 16px; border-bottom:1px solid var(--border); margin-bottom:8px;">
        <div style="font-weight:700; font-size:.95rem;">🧸 PEP落とし物管理</div>
        <div style="font-size:.7rem; color:var(--text-muted);">PlayEarth Park</div>
      </div>
      <a href="{{ route('dashboard') }}"
         class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i> ダッシュボード
      </a>
      <a href="{{ route('found-items.index') }}"
         class="nav-item {{ request()->routeIs('found-items.*') ? 'active' : '' }}">
        <i data-lucide="package-search"></i> 拾得物一覧
      </a>
      <a href="{{ route('lost-reports.index') }}"
         class="nav-item {{ request()->routeIs('lost-reports.*') ? 'active' : '' }}">
        <i data-lucide="file-search"></i> 紛失届一覧
      </a>
      <a href="{{ route('matches.index') }}"
         class="nav-item {{ request()->routeIs('matches.*') ? 'active' : '' }}">
        <i data-lucide="git-compare-arrows"></i> マッチング確認
      </a>
      <a href="{{ route('export.police-form') }}"
         class="nav-item {{ request()->routeIs('export.*') ? 'active' : '' }}">
        <i data-lucide="printer"></i> 警察提出出力
      </a>
    </div>
  </div>
</div>

{{-- ===== App shell ===== --}}
<div class="app-shell">

  {{-- ===== Desktop Sidebar ===== --}}
  <aside class="sidebar" role="navigation" aria-label="メインナビゲーション">
    <div class="sidebar-logo">
      <h1>🧸 PEP落とし物管理</h1>
      <span>PlayEarth Park System</span>
    </div>

    <nav class="sidebar-nav">
      <a href="{{ route('dashboard') }}"
         class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i>
        ダッシュボード
      </a>
      <a href="{{ route('found-items.index') }}"
         class="nav-item {{ request()->routeIs('found-items.*') ? 'active' : '' }}">
        <i data-lucide="package-search"></i>
        拾得物一覧
      </a>
      <a href="{{ route('lost-reports.index') }}"
         class="nav-item {{ request()->routeIs('lost-reports.*') ? 'active' : '' }}">
        <i data-lucide="file-search"></i>
        紛失届一覧
      </a>
      <a href="{{ route('matches.index') }}"
         class="nav-item {{ request()->routeIs('matches.*') ? 'active' : '' }}">
        <i data-lucide="git-compare-arrows"></i>
        マッチング確認
      </a>
      <a href="{{ route('export.police-form') }}"
         class="nav-item {{ request()->routeIs('export.*') ? 'active' : '' }}">
        <i data-lucide="printer"></i>
        警察提出出力
      </a>
    </nav>

    <div class="sidebar-footer">
      <div>© {{ date('Y') }} PlayEarth Park</div>
      <div style="margin-top:3px">v1.0.0</div>
    </div>
  </aside>

  {{-- ===== Main content ===== --}}
  <main class="main-content" id="main-content">
    @yield('content')
  </main>

</div>{{-- /.app-shell --}}

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

{{-- ===== Scripts ===== --}}
<script src="/js/app.js"></script>
@stack('scripts')

</body>
</html>
