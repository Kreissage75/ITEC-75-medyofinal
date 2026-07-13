@extends('layouts.app')

@section('title', 'Executive Dashboard')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
  <div class="container-fluid px-0">
    <div class="dashboard-container">

      <div id="dashboardContent">

        <!-- Page header -->
        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-2 mb-3">
          <div>
            <h1 class="dashboard-title mb-0">Executive Dashboard</h1>
            <p class="dashboard-subtitle mb-0">Overview of support operations and performance</p>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="date-chip"><i class="bi bi-calendar3"></i> <span id="todayDate"></span></span>
            <button class="btn btn-outline-success btn-sm rounded-pill px-3" id="refreshBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
          </div>
        </div>

        <!-- STEP 1-2: KPI Summary Cards -->
        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-3">
          <div class="col">
            <div class="card kpi-card h-100 shadow-soft">
              <div class="card-body">
                <div class="kpi-icon bg-green"><i class="bi bi-ticket-perforated-fill"></i></div>
                <div class="kpi-label">Open Tickets</div>
                <div class="kpi-value" data-target="open">—</div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card kpi-card h-100 shadow-soft">
              <div class="card-body">
                <div class="kpi-icon bg-warning"><i class="bi bi-clock-history"></i></div>
                <div class="kpi-label">Pending Tickets</div>
                <div class="kpi-value" data-target="pending">—</div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card kpi-card h-100 shadow-soft">
              <div class="card-body">
                <div class="kpi-icon bg-green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="kpi-label">Resolved Tickets</div>
                <div class="kpi-value" data-target="resolved">—</div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card kpi-card h-100 shadow-soft">
              <div class="card-body">
                <div class="kpi-icon bg-green"><i class="bi bi-shield-check"></i></div>
                <div class="kpi-label">SLA Compliance</div>
                <div class="kpi-value" data-target="overall_sla">—</div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card kpi-card h-100 shadow-soft">
              <div class="card-body">
                <div class="kpi-icon bg-blue"><i class="bi bi-stopwatch-fill"></i></div>
                <div class="kpi-label">Avg. Response Time</div>
                <div class="kpi-value" data-target="avg_response">—</div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card kpi-card h-100 shadow-soft">
              <div class="card-body">
                <div class="kpi-icon bg-danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="kpi-label">Overdue SLA</div>
                <div class="kpi-value" data-target="overdue_sla">—</div>
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 3: Chart widgets - row of 4 -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-3">
          <div class="col">
            <div class="card h-100 shadow-soft">
              <div class="card-header"><h6><i class="bi bi-pie-chart-fill me-1 text-success"></i> Ticket Status</h6></div>
              <div class="card-body">
                <div class="chart-wrapper"><canvas id="ticketStatusChart"></canvas></div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card h-100 shadow-soft">
              <div class="card-header"><h6><i class="bi bi-bar-chart-fill me-1 text-success"></i> Weekly Ticket Volume</h6></div>
              <div class="card-body">
                <div class="chart-wrapper"><canvas id="weeklyVolumeChart"></canvas></div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card h-100 shadow-soft">
              <div class="card-header"><h6><i class="bi bi-shield-check me-1 text-success"></i> SLA Compliance <small class="text-muted text-normal">(30 days)</small></h6></div>
              <div class="card-body">
                <div class="chart-wrapper"><canvas id="slaGaugeChart"></canvas></div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card h-100 shadow-soft">
              <div class="card-header"><h6><i class="bi bi-graph-up me-1 text-success"></i> Ticket Trend <small class="text-muted text-normal">(30 days)</small></h6></div>
              <div class="card-body">
                <div class="chart-wrapper"><canvas id="trendChart"></canvas></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Priority / Status breakdown / SLA Alerts - row of 3 -->
        <div class="row row-cols-1 row-cols-lg-3 g-3 mb-3">
          <div class="col">
            <div class="card h-100 shadow-soft">
              <div class="card-header"><h6><i class="bi bi-flag-fill me-1 text-success"></i> Priority Distribution</h6></div>
              <div class="card-body">
                <div class="chart-wrapper"><canvas id="priorityChart"></canvas></div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card h-100 shadow-soft">
              <div class="card-header"><h6><i class="bi bi-layout-text-window-reverse me-1 text-success"></i> Tickets by Status</h6></div>
              <div class="card-body">
                <div class="chart-wrapper"><canvas id="statusBarChart"></canvas></div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card h-100 shadow-soft">
              <div class="card-header"><h6><i class="bi bi-bell-fill me-1 text-success"></i> SLA Alerts</h6></div>
              <div class="card-body d-flex flex-column gap-2" id="alertsPanel"></div>
            </div>
          </div>
        </div>

        <!-- STEP 4-5: Recent Tickets + Agent Performance + Side widgets -->
        <div class="row row-cols-1 row-cols-xl-3 g-3 mb-3">
          <div class="col-xl-5">
            <div class="card h-100 shadow-soft">
              <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0"><i class="bi bi-list-ul me-1 text-success"></i> Recent Tickets</h6>
                <a href="{{ route('tickets.index') }}" class="small-link">View all <i class="bi bi-arrow-right"></i></a>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table data-table mb-0" id="recentTable">
                    <thead>
                      <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned</th>
                        <th>Created</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-4">
            <div class="card h-100 shadow-soft">
              <div class="card-header"><h6 class="mb-0"><i class="bi bi-person-badge-fill me-1 text-success"></i> Agent Performance</h6></div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table data-table mb-0" id="agentsTable">
                    <thead>
                      <tr>
                        <th>Agent</th>
                        <th>Assigned</th>
                        <th>Resolved</th>
                        <th>Avg Resolution</th>
                        <th>SLA %</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 d-flex flex-column gap-3">
            <!-- STEP 7: Self Service + Customer Communication widgets -->
            <div class="card shadow-soft flex-fill">
              <div class="card-header"><h6 class="mb-0"><i class="bi bi-book-fill me-1 text-success"></i> Self Service Portal</h6></div>
              <div class="card-body">
                <div class="row row-cols-2 g-2 text-center">
                  <div class="col"><div class="mini-stat" data-target="articles">—</div><div class="mini-label">Articles</div></div>
                  <div class="col"><div class="mini-stat" data-target="faq_views">—</div><div class="mini-label">FAQ Views</div></div>
                  <div class="col"><div class="mini-stat" data-target="searches">—</div><div class="mini-label">Searches</div></div>
                  <div class="col"><div class="mini-stat" data-target="helpful">—</div><div class="mini-label">Helpful Votes</div></div>
                </div>
              </div>
            </div>
            <div class="card shadow-soft flex-fill">
              <div class="card-header"><h6 class="mb-0"><i class="bi bi-chat-dots-fill me-1 text-success"></i> Customer Communication</h6></div>
              <div class="card-body">
                <div class="row row-cols-2 g-2 text-center">
                  <div class="col"><div class="mini-stat" data-target="unread_messages">—</div><div class="mini-label">Unread Messages</div></div>
                  <div class="col"><div class="mini-stat" data-target="open_conversations">—</div><div class="mini-label">Open Conversations</div></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 8: Quick Actions -->
        <div class="card shadow-soft mb-2">
          <div class="card-body">
            <div class="d-flex align-items-center flex-wrap gap-2">
              <span class="quick-actions-label me-2">Quick Actions</span>
              <a href="{{ route('tickets.create') }}" class="btn btn-qa btn-qa-green"><i class="bi bi-plus-lg"></i> Create Ticket</a>
              <a href="{{ route('tickets.index') }}" class="btn btn-qa btn-qa-blue"><i class="bi bi-list-task"></i> View Tickets</a>
              <a href="{{ route('sla.index') }}" class="btn btn-qa btn-qa-purple"><i class="bi bi-shield-check"></i> SLA Tracking</a>
              <a href="{{ route('support.index') }}" class="btn btn-qa btn-qa-orange"><i class="bi bi-journal-text"></i> Knowledge Base</a>
              <a href="{{ route('customers.index') }}" class="btn btn-qa btn-qa-teal"><i class="bi bi-people-fill"></i> Customer List</a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="{{ asset('js/dashboard.js') }}" defer></script>
@endpush
