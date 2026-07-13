<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>AMBATUGROW - @yield('title', 'Support Center')</title>
<style>
  :root {
    --green: #5c9a3b;
    --green-dark: #4d8531;
    --green-pale: #eef6ec;
    --status-open-bg: #cdeec2;
    --status-open-text: #2f7a1f;
    --orange-bg: #fdeec2;
    --orange-text: #9a7712;
    --border: #dedede;
    --text-dark: #222;
    --text-muted: #8a8a8a;
    --placeholder-bg: #dcdcdc;
  }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; color: var(--text-dark); background: #fff; }

  .navbar { display: flex; align-items: center; justify-content: space-between; background: var(--green); color: #fff; padding: 10px 24px; }
  .navbar-left { display: flex; align-items: center; gap: 24px; }
  .brand { display: flex; align-items: center; gap: 8px; font-weight: 700; letter-spacing: 0.5px; font-size: 15px; }
  .brand-icon { width: 18px; height: 18px; display: inline-block; }
  .nav-link { font-size: 12px; letter-spacing: 0.5px; opacity: 0.95; }
  .navbar-right { display: flex; align-items: center; gap: 18px; }
  .search-box { background: #fff; border-radius: 16px; padding: 6px 14px; width: 220px; display: flex; align-items: center; gap: 6px; color: #999; font-size: 12px; }
  .bell { width: 16px; height: 16px; opacity: 0.9; }
  .user-chip { display: flex; align-items: center; gap: 8px; }
  .avatar { width: 28px; height: 28px; border-radius: 50%; background: #cfe8bd; border: 2px solid #fff; }
  .user-info { line-height: 1.1; }
  .user-name { font-size: 11px; font-weight: 700; }
  .user-role { font-size: 9px; opacity: 0.85; }
  .caret { font-size: 10px; opacity: 0.85; }

  .layout { display: flex; min-height: calc(100vh - 46px); }

  .sidebar { width: 190px; border-right: 1px solid var(--border); padding: 20px 14px; display: flex; flex-direction: column; align-items: stretch; flex-shrink: 0; }
  .sidebar-btn {
    display: block; border: 1.5px solid #333; border-radius: 18px; padding: 10px 12px; font-size: 10.5px; font-weight: 600;
    letter-spacing: 0.3px; text-align: center; margin-bottom: 12px; cursor: pointer; color: #333; background: #fff;
    font-family: inherit; width: 100%; transition: background 0.15s, color 0.15s, border-color 0.15s; text-decoration: none;
  }
  .sidebar-btn:hover { background: #f2f7ef; }
  .sidebar-btn.active { background: var(--green); color: #fff; border-color: var(--green); }
  .sidebar-btn.active:hover { background: var(--green-dark); }
  .sidebar-tree { margin-top: auto; text-align: center; padding-top: 30px; font-size: 60px; line-height: 1; }

  .main { flex: 1; padding: 20px 24px; display: flex; flex-direction: column; gap: 18px; }

  .card { border: 1px solid var(--border); border-radius: 6px; padding: 16px; background: #fff; }
  .card-head { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 14px; }
  .card-title { font-size: 12px; font-weight: 700; letter-spacing: 0.3px; color: var(--text-dark); }

  .btn { display: inline-flex; align-items: center; gap: 8px; border-radius: 8px; font-size: 12.5px; font-weight: 700; padding: 9px 14px; cursor: pointer; border: 1px solid var(--border); background: #fff; color: var(--text-dark); font-family: inherit; text-decoration: none; }
  .btn:hover { filter: brightness(0.98); }
  .btn.green { background: var(--green); color: #fff; border-color: var(--green); }
  .btn.green:hover { background: var(--green-dark); }

  table.data-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
  table.data-table th { text-align: left; font-size: 10.5px; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border); padding: 8px 10px; }
  table.data-table td { padding: 10px; border-bottom: 1px solid var(--border); }
  table.data-table tr:hover { background: var(--green-pale); }

  .pill { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10.5px; font-weight: 700; }
  .pill.open { background: var(--status-open-bg); color: var(--status-open-text); }
  .pill.pending { background: var(--orange-bg); color: var(--orange-text); }
  .pill.resolved { background: #dcdcdc; color: #555; }
  .pill.high { background: #fddede; color: #a12b2b; }
  .pill.medium { background: var(--orange-bg); color: var(--orange-text); }
  .pill.low { background: var(--green-pale); color: var(--green-dark); }
  .pill.general { background: #e4e4e4; color: #555; }
  .pill.active { background: var(--status-open-bg); color: var(--status-open-text); }

  .footer-bar { height: 40px; background: var(--green); }

  @media (max-width: 640px) {
    .layout { flex-direction: column; }
    .sidebar { width: 100%; }
    .sidebar-tree { display: none; }
  }
</style>
@stack('styles')
</head>
<body>

  <div class="navbar">
    <div class="navbar-left">
      <div class="brand">
        <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M12 2C7 6 4 10 4 14a8 8 0 0016 0c0-4-3-8-8-12z"/></svg>
        AMBATUGROW
      </div>
      <div class="nav-link">SUPPORT CENTER</div>
    </div>
    <div class="navbar-right">
      <div class="search-box">&#128269; &nbsp; Search</div>
      <svg class="bell" viewBox="0 0 24 24" fill="white"><path d="M12 22a2 2 0 002-2h-4a2 2 0 002 2zm6-6v-5a6 6 0 10-12 0v5l-2 2v1h16v-1l-2-2z"/></svg>
      <div class="user-chip">
        <div class="avatar"></div>
        <div class="user-info">
          <div class="user-name">{{ auth()->user()->name ?? 'USERS NAME' }}</div>
          <div class="user-role">SUPPORT AGENT</div>
        </div>
        <span class="caret">&#9662;</span>
      </div>
    </div>
  </div>

  <div class="layout">
    <div class="sidebar">
      <a href="{{ route('dashboard') }}" class="sidebar-btn {{ request()->routeIs('dashboard') ? 'active' : '' }}">DASHBOARD</a>
      <a href="{{ route('tickets.index') }}" class="sidebar-btn {{ request()->routeIs('tickets.*') ? 'active' : '' }}">TICKET MANAGEMENT SYSTEM</a>
      <a href="{{ route('support.index') }}" class="sidebar-btn {{ request()->routeIs('support.*') ? 'active' : '' }}">SELF SERVICE PORTAL</a>
      <a href="{{ route('customers.index') }}" class="sidebar-btn {{ request()->routeIs('customers.*') ? 'active' : '' }}">CUSTOMER COMMUNICATION</a>
      <a href="{{ route('sla.index') }}" class="sidebar-btn {{ request()->routeIs('sla.*') ? 'active' : '' }}">SLA TRACKING</a>
      <div class="sidebar-tree">&#127795;</div>
    </div>

    <div class="main">
      @yield('content')
    </div>
  </div>

  <div class="footer-bar"></div>

  @stack('scripts')
</body>
</html>
