@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
  .stats-row { display: flex; align-items: center; gap: 14px; }
  .stat-card { border: 1px solid var(--border); border-radius: 6px; padding: 12px 18px; display: flex; align-items: center; gap: 10px; }
  .stat-icon { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #fff; flex-shrink: 0; }
  .stat-icon.green { background: var(--green); }
  .stat-icon.orange { background: #e8a23c; }
  .stat-label { font-size: 10px; color: var(--text-muted); line-height: 1.2; }
  .stat-value { font-size: 16px; font-weight: 700; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
  .card { min-height: 200px; cursor: pointer; transition: box-shadow .15s, border-color .15s; }
  .card:hover { box-shadow: 0 4px 14px rgba(0,0,0,.06); border-color: var(--green); }
  .card-arrow { color: var(--text-muted); }
  .empty-sub { font-size: 10.5px; color: var(--text-muted); }
@endsection

@section('content')
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon green">&#127915;</div>
      <div><div class="stat-label">Open Tickets</div><div class="stat-value">{{ $stats['open_tickets'] }}</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange">&#9201;</div>
      <div><div class="stat-label">Avg Response</div><div class="stat-value">{{ $stats['avg_response'] }}</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green">&#10004;</div>
      <div><div class="stat-label">Resolved</div><div class="stat-value">{{ $stats['resolved'] }}</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange">&#128197;</div>
      <div><div class="stat-label">SLA Due Today</div><div class="stat-value">{{ $stats['sla_due_today'] }}</div></div>
    </div>
    <div style="margin-left:auto; font-size:44px;">&#127795;</div>
  </div>

  <div class="grid">
    <a href="{{ route('tickets.index') }}" class="card" style="text-decoration:none;color:inherit;">
      <div class="card-head"><div class="card-title">TICKET MANAGEMENT SYSTEM</div><div class="card-arrow">&rarr;</div></div>
      @forelse($recentTickets as $t)
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed var(--border);font-size:11px;">
          <span>{{ $t->code }} &middot; {{ $t->subject }}</span>
          <span class="pill {{ $t->statusPillClass() }}">{{ ucfirst($t->status) }}</span>
        </div>
      @empty
        <div class="empty-sub">No tickets to show yet.</div>
      @endforelse
    </a>

    <a href="{{ route('support.index') }}" class="card" style="text-decoration:none;color:inherit;">
      <div class="card-head"><div class="card-title">SELF SERVICE PORTAL</div><div class="card-arrow">&rarr;</div></div>
      <div class="empty-sub">Article views, FAQs, and self-help usage summarize here.</div>
    </a>

    <a href="{{ route('customers.index') }}" class="card" style="text-decoration:none;color:inherit;">
      <div class="card-head"><div class="card-title">CUSTOMER COMMUNICATION</div><div class="card-arrow">&rarr;</div></div>
      <div class="empty-sub">{{ $customerCount }} customers on file.</div>
    </a>

    <a href="{{ route('sla.index') }}" class="card" style="text-decoration:none;color:inherit;">
      <div class="card-head"><div class="card-title">SLA TRACKING</div><div class="card-arrow">&rarr;</div></div>
      <div class="empty-sub">View compliance charts and SLA rules.</div>
    </a>
  </div>
@endsection
