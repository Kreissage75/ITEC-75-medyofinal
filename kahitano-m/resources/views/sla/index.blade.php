@extends('layouts.app')

@section('title', 'SLA Monitoring')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/sla.css') }}">
@endpush

@section('content')
  @php
    $today = today();
    $yesterday = today()->subDay();
    $last7 = today()->subDays(6);
    $last30 = today()->subDays(29);
    $thisMonthStart = today()->startOfMonth();
    $lastMonthStart = today()->subMonthNoOverflow()->startOfMonth();
    $lastMonthEnd = today()->subMonthNoOverflow()->endOfMonth();
  @endphp

  <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:16px; align-items:flex-start;">
    <div style="display:flex; flex-wrap:wrap; gap:10px;">
      <button type="button" class="sla-range-button {{ $range === 'today' ? 'active' : '' }}" data-range="today" data-from="{{ $today->toDateString() }}" data-to="{{ $today->toDateString() }}">Today</button>
      <button type="button" class="sla-range-button {{ $range === 'yesterday' ? 'active' : '' }}" data-range="yesterday" data-from="{{ $yesterday->toDateString() }}" data-to="{{ $yesterday->toDateString() }}">Yesterday</button>
      <button type="button" class="sla-range-button {{ $range === 'last7' ? 'active' : '' }}" data-range="last7" data-from="{{ $last7->toDateString() }}" data-to="{{ $today->toDateString() }}">Last 7 Days</button>
      <button type="button" class="sla-range-button {{ $range === 'last30' ? 'active' : '' }}" data-range="last30" data-from="{{ $last30->toDateString() }}" data-to="{{ $today->toDateString() }}">Last 30 Days</button>
      <button type="button" class="sla-range-button {{ $range === 'this_month' ? 'active' : '' }}" data-range="this_month" data-from="{{ $thisMonthStart->toDateString() }}" data-to="{{ $today->toDateString() }}">This Month</button>
      <button type="button" class="sla-range-button {{ $range === 'last_month' ? 'active' : '' }}" data-range="last_month" data-from="{{ $lastMonthStart->toDateString() }}" data-to="{{ $lastMonthEnd->toDateString() }}">Last Month</button>
    </div>

    <div style="display:flex; gap:12px; flex-wrap:wrap;">
      <button id="add-rule-button" class="sla-button">+ Add New SLA Rule</button>
      <button id="export-report-button" class="sla-button export">Export SLA Report</button>
    </div>
  </div>

  <div class="sla-kpis">
    <div class="sla-card green">
      <span class="label">On Track</span>
      <span class="value" id="on-track-count">{{ $kpis['on_track'] }}</span>
      <span class="subtle">Tickets currently meeting SLA goals.</span>
    </div>
    <div class="sla-card orange">
      <span class="label">At Risk</span>
      <span class="value" id="at-risk-count">{{ $kpis['at_risk'] }}</span>
      <span class="subtle">Tickets approaching a resolution deadline.</span>
    </div>
    <div class="sla-card red">
      <span class="label">Breached</span>
      <span class="value" id="breached-count">{{ $kpis['breached'] }}</span>
      <span class="subtle">Tickets past SLA resolution targets.</span>
    </div>
    <div class="sla-card dark">
      <span class="label">Average Resolution Time</span>
      <span class="value" id="avg-resolution-value">{{ $kpis['avg_resolution'] }}</span>
      <span class="subtle">Goal: <strong id="avg-resolution-goal">{{ $kpis['resolution_goal'] }}</strong></span>
    </div>
    <div class="sla-card dark">
      <span class="label">Average Response Time</span>
      <span class="value" id="avg-response-value">{{ $kpis['avg_response'] }}</span>
      <span class="subtle">Goal: <strong id="avg-response-goal">{{ $kpis['response_goal'] }}</strong></span>
    </div>
  </div>

  <div class="sla-grid">
    <div class="sla-card sla-chart-card">
      <div class="sla-chart-header">
        <div>
          <span class="label">SLA Compliance Trend</span>
          <h3>Trend performance over time</h3>
        </div>
      </div>
      <div class="chart-container" style="height:320px;">
        <canvas id="trend-chart"></canvas>
      </div>
    </div>

    <div class="sla-panel">
      <div class="sla-card sla-chart-card">
        <div class="sla-chart-header">
          <div>
            <span class="label">SLA Status Breakdown</span>
            <h3>Ticket distribution</h3>
          </div>
        </div>
        <div class="chart-container" style="height:240px;">
          <canvas id="breakdown-chart"></canvas>
        </div>
      </div>

      <div class="sla-card sla-chart-card" style="display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center; gap:18px;">
        <div class="sla-chart-header" style="width:100%; justify-content:center;">
          <div>
            <span class="label">Overall SLA Compliance</span>
            <h3>Current adherence score</h3>
          </div>
        </div>
        <div style="position:relative; width:220px; height:220px;">
          <div class="chart-container" style="height:220px; width:220px;">
            <canvas id="compliance-circle"></canvas>
          </div>
        </div>
        <div style="font-size:28px; font-weight:800; color:#111827;" id="overall-circle-label">{{ $kpis['overall'] }}%</div>
      </div>
    </div>
  </div>

  <div class="sla-card" style="overflow-x:auto;">
    <div class="sla-chart-header" style="margin-bottom:18px;">
      <div>
        <span class="label">SLA Rules</span>
        <h3>Priority rules and targets</h3>
      </div>
    </div>

    <table class="sla-table">
      <thead>
        <tr>
          <th>Priority</th>
          <th>Rule</th>
          <th>Response Time Goal</th>
          <th>Resolution Time Goal</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="sla-rules-table-body">
        @foreach($rules as $rule)
          <tr>
            <td><span class="sla-pill {{ $rule->pillClass() }}">{{ $rule->priority }}</span></td>
            <td>{{ $rule->name }}</td>
            <td>{{ $rule->formatted('response') }}</td>
            <td>{{ $rule->formatted('resolution') }}</td>
            <td><span class="sla-pill {{ $rule->active ? 'active' : 'inactive' }}">{{ $rule->active ? 'Active' : 'Inactive' }}</span></td>
            <td class="sla-actions">
              <button type="button" class="sla-button secondary" data-action="edit" data-id="{{ $rule->id }}">Edit</button>
              <button type="button" class="sla-button secondary" data-action="delete" data-id="{{ $rule->id }}">Delete</button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="sla-modal-backdrop" id="sla-rule-backdrop" aria-hidden="true">
    <div class="sla-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
      <header>
        <div>
          <h2 id="modal-title">Add New SLA Rule</h2>
          <p class="subtle">Create or update SLA policies without reloading the page.</p>
        </div>
        <button type="button" id="modal-close-button">&times;</button>
      </header>

      <form id="sla-rule-form" class="sla-form" data-method="POST" data-id="">
        <div class="row">
          <label>Priority
              <select id="rule-priority" name="priority">
                <option>High</option>
                <option>Medium</option>
                <option>Low</option>
                <option>General</option>
              </select>
          </label>
          <label>Name
              <input id="rule-name" name="name" type="text" required />
          </label>
        </div>

        <label>Description
          <textarea id="rule-description" name="description" rows="4"></textarea>
        </label>

        <div class="row">
          <label>Response Time Goal (hrs)
            <input id="rule-response-hours" name="response_hours" type="number" min="0" value="2" required />
          </label>
          <label>Response Time Goal (mins)
            <input id="rule-response-minutes" name="response_minutes" type="number" min="0" max="59" value="0" required />
          </label>
        </div>

        <div class="row">
          <label>Resolution Time Goal (hrs)
            <input id="rule-resolution-hours" name="resolution_hours" type="number" min="0" value="24" required />
          </label>
          <label>Resolution Time Goal (mins)
            <input id="rule-resolution-minutes" name="resolution_minutes" type="number" min="0" max="59" value="0" required />
          </label>
        </div>

        <label class="toggle">
          <input id="rule-active" name="active" type="checkbox" checked /> Active
        </label>

        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:8px;">
          <button type="button" class="sla-button secondary" onclick="document.getElementById('sla-rule-backdrop').classList.remove('active')">Cancel</button>
          <button id="submit-button" type="submit" class="sla-button">Save Rule</button>
        </div>
      </form>
    </div>
  </div>

  <div class="sla-modal-backdrop" id="sla-export-backdrop" aria-hidden="true">
    <div class="sla-modal" role="dialog" aria-modal="true" aria-labelledby="export-modal-title">
      <header>
        <div>
          <h2 id="export-modal-title">Export SLA Report</h2>
          <p class="subtle">Choose your report format and date range for export.</p>
        </div>
        <button type="button" id="export-modal-close">&times;</button>
      </header>

      <form id="sla-export-form" class="sla-form">
        <label>Report Type
          <input name="report_type" type="text" value="SLA Monitoring" required />
        </label>

        <div class="row">
          <label>Date From
            <input id="range-from" name="from" type="date" value="{{ $from->toDateString() }}" />
          </label>
          <label>Date To
            <input id="range-to" name="to" type="date" value="{{ $to->toDateString() }}" />
          </label>
        </div>

        <label>Format
          <select name="format" required>
            <option value="pdf">PDF</option>
            <option value="excel">Excel</option>
            <option value="csv" selected>CSV</option>
          </select>
        </label>

        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:8px;">
          <button type="button" class="sla-button secondary" onclick="document.getElementById('sla-export-backdrop').classList.remove('active')">Cancel</button>
          <button type="submit" class="sla-button export">Export Report</button>
        </div>
      </form>
    </div>
  </div>

  <div id="sla-toast" class="sla-toast"></div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/sla.js') }}"></script>
@endpush
