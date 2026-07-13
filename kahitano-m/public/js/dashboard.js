// AMBATUGROW Executive Dashboard — consumes existing /dashboard/data endpoint only.
(function () {
  const API = '/dashboard/data';
  const REFRESH_INTERVAL = 60000; // 60 seconds
  const charts = {};
  let refreshTimer = null;
  let isLoading = false;

  const STATUS_COLORS = { open: '#5c9a3b', pending: '#e8a23c', resolved: '#9aa0a6' };
  const PRIORITY_COLORS = { High: '#e5484d', Medium: '#e8a23c', Low: '#5c9a3b', General: '#3d84c6' };

  function el(q) { return document.querySelector(q); }

  function showLoading() {
    isLoading = true;
    const btn = el('#refreshBtn');
    if (btn) btn.classList.add('is-refreshing');
  }

  function hideLoading() {
    isLoading = false;
    const btn = el('#refreshBtn');
    if (btn) btn.classList.remove('is-refreshing');
  }

  function showToast(message, type = 'danger') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show`;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.minWidth = '300px';
    toast.innerHTML = `${message} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
  }

  function setTodayDate() {
    const target = el('#todayDate');
    if (!target) return;
    target.textContent = new Date().toLocaleString(undefined, {
      year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
    });
  }

  function setKpis(data) {
    const overview = data.overview || {};
    const alerts = data.alerts || {};
    const mapping = {
      open: overview.open ?? 0,
      pending: overview.pending ?? 0,
      resolved: overview.resolved ?? 0,
      overall_sla: (overview.overall_sla ?? 0) + '%',
      avg_response: overview.avg_response ?? '—',
      overdue_sla: alerts.overdue ?? 0,
    };
    Object.keys(mapping).forEach(k => {
      const target = document.querySelector(`[data-target="${k}"]`);
      if (target) target.textContent = mapping[k];
    });
  }

  function setModules(data) {
    const modules = data.modules || {};
    ['articles', 'faq_views', 'searches', 'helpful', 'unread_messages', 'open_conversations'].forEach(k => {
      const target = document.querySelector(`[data-target="${k}"]`);
      if (target) target.textContent = modules[k] ?? 0;
    });
  }

  function safeDestroy(key) {
    if (charts[key]) {
      try { charts[key].destroy(); } catch (e) { /* noop */ }
      delete charts[key];
    }
  }

  function baseOptions(extra = {}) {
    return Object.assign({ responsive: true, maintainAspectRatio: false, animation: { duration: 350 } }, extra);
  }

  // Ticket Status donut — uses status breakdown (categories payload: Open/Pending/Resolved)
  function renderTicketStatus(data) {
    const ctx = document.getElementById('ticketStatusChart');
    const cats = data.categories || {};
    const labels = cats.labels || [];
    const values = cats.values || [];
    safeDestroy('ticketStatus');
    if (!values.some(v => v > 0)) {
      charts.ticketStatus = null;
      ctx.getContext('2d').clearRect(0, 0, ctx.width, ctx.height);
      return;
    }
    const colors = labels.map(l => STATUS_COLORS[l.toLowerCase()] || '#9aa0a6');
    charts.ticketStatus = new Chart(ctx, {
      type: 'doughnut',
      data: { labels, datasets: [{ data: values, backgroundColor: colors, borderColor: '#fff', borderWidth: 2 }] },
      options: baseOptions({ plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } })
    });
  }

  // Weekly Ticket Volume bar — 7-day trend
  function renderWeekly(weeklyTrend) {
    const ctx = document.getElementById('weeklyVolumeChart');
    safeDestroy('weekly');
    const labels = weeklyTrend.labels || [];
    const vals = weeklyTrend.data || [];
    charts.weekly = new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets: [{ label: 'Tickets', data: vals, backgroundColor: '#5c9a3b', borderRadius: 5, maxBarThickness: 42 }] },
      options: baseOptions({
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
      })
    });
  }

  // SLA Compliance gauge (half doughnut)
  function renderSlaGauge(data) {
    const ctx = document.getElementById('slaGaugeChart');
    safeDestroy('slaGauge');
    const val = Math.max(0, Math.min(100, data.overview.overall_sla ?? 0));
    const remaining = 100 - val;
    const color = val >= 90 ? '#5c9a3b' : val >= 75 ? '#e8a23c' : '#e5484d';
    charts.slaGauge = new Chart(ctx, {
      type: 'doughnut',
      data: { labels: ['Compliant', 'Remaining'], datasets: [{ data: [val, remaining], backgroundColor: [color, '#eef1ee'], borderColor: '#fff', borderWidth: 2 }] },
      options: baseOptions({
        circumference: 180,
        rotation: 270,
        cutout: '72%',
        plugins: { tooltip: { enabled: false }, legend: { display: false } }
      }),
      plugins: [{
        id: 'gaugeCenterText',
        beforeDraw(chart) {
          const { width, height, ctx } = chart;
          ctx.save();
          ctx.textAlign = 'center';
          ctx.fillStyle = color;
          ctx.font = '700 26px Inter, sans-serif';
          ctx.fillText(val + '%', width / 2, height - 18);
          ctx.font = '600 11px Inter, sans-serif';
          ctx.fillStyle = '#8a8a8a';
          ctx.fillText('Compliance', width / 2, height + 2);
          ctx.restore();
        }
      }]
    });
  }

  // Ticket Trend (30 days) line
  function renderTrend(trend) {
    const ctx = document.getElementById('trendChart');
    safeDestroy('trend');
    const labels = trend.labels || [];
    const vals = trend.data || [];
    charts.trend = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Tickets',
          data: vals,
          borderColor: '#3ea96e',
          backgroundColor: 'rgba(62,169,110,0.12)',
          fill: true,
          tension: 0.3,
          borderWidth: 2,
          pointRadius: 2,
          pointBackgroundColor: '#3ea96e'
        }]
      },
      options: baseOptions({
        plugins: { legend: { display: false } },
        scales: { x: { ticks: { maxTicksLimit: 7 } }, y: { beginAtZero: true, ticks: { precision: 0 } } }
      })
    });
  }

  // Priority Distribution pie
  function renderPriority(data) {
    const ctx = document.getElementById('priorityChart');
    safeDestroy('priority');
    const priority = data.priority || {};
    const labels = priority.labels || [];
    const values = priority.values || [];
    if (!values.some(v => v > 0)) return;
    const colors = labels.map(l => PRIORITY_COLORS[l] || '#9aa0a6');
    charts.priority = new Chart(ctx, {
      type: 'pie',
      data: { labels, datasets: [{ data: values, backgroundColor: colors, borderColor: '#fff', borderWidth: 2 }] },
      options: baseOptions({ plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } } })
    });
  }

  // Tickets by Status horizontal bar (same categories payload, different view)
  function renderStatusBar(data) {
    const ctx = document.getElementById('statusBarChart');
    safeDestroy('statusBar');
    const cats = data.categories || {};
    const labels = cats.labels || [];
    const values = cats.values || [];
    if (!labels.length) return;
    const colors = labels.map(l => STATUS_COLORS[l.toLowerCase()] || '#5c9a3b');
    charts.statusBar = new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets: [{ data: values, backgroundColor: colors, borderRadius: 5 }] },
      options: baseOptions({
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
      })
    });
  }

  function renderAgents(data) {
    const tbody = document.querySelector('#agentsTable tbody');
    tbody.innerHTML = '';
    const agents = data.agents || [];
    if (agents.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No agent data yet</td></tr>';
      return;
    }
    agents.forEach(a => {
      const tr = document.createElement('tr');
      const slaPercent = a.sla_rate ?? null;
      const slaDisplay = slaPercent === null ? '—' : slaPercent + '%';
      const slaColor = slaPercent === null ? 'secondary' : slaPercent >= 90 ? 'success' : slaPercent >= 75 ? 'warning' : 'danger';
      tr.innerHTML = `
        <td><strong>${a.agent}</strong></td>
        <td><span class="badge bg-primary-subtle text-primary-emphasis">${a.assigned}</span></td>
        <td><span class="badge bg-success-subtle text-success-emphasis">${a.resolved}</span></td>
        <td><small>${a.avg_resolution ? a.avg_resolution + 'm' : '—'}</small></td>
        <td style="min-width:110px;">
          <div class="progress">
            <div class="progress-bar bg-${slaColor}" style="width:${slaPercent ?? 0}%">${slaDisplay}</div>
          </div>
        </td>`;
      tbody.appendChild(tr);
    });
  }

  function renderRecent(data) {
    const tbody = document.querySelector('#recentTable tbody');
    tbody.innerHTML = '';
    const recent = data.recent || [];
    if (recent.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No recent tickets</td></tr>';
      return;
    }
    recent.forEach(t => {
      const tr = document.createElement('tr');
      tr.style.cursor = 'pointer';
      tr.addEventListener('click', () => { window.location.href = '/tickets/' + t.id; });
      const priorityClass = t.priority === 'High' ? 'danger' : t.priority === 'Medium' ? 'warning' : t.priority === 'Low' ? 'success' : 'secondary';
      const statusKey = (t.status || '').toLowerCase();
      const statusClass = statusKey === 'open' ? 'success' : statusKey === 'pending' ? 'warning' : statusKey === 'resolved' ? 'secondary' : 'secondary';
      tr.innerHTML = `
        <td><code>${t.code ?? ('#' + t.id)}</code></td>
        <td><small>${t.subject ?? ''}</small></td>
        <td><span class="badge bg-${priorityClass}">${t.priority ?? '—'}</span></td>
        <td><span class="badge bg-${statusClass}">${t.status ?? '—'}</span></td>
        <td><small>${t.assigned_to ?? '—'}</small></td>
        <td><small>${t.created_at ? new Date(t.created_at).toLocaleDateString() : '—'}</small></td>`;
      tbody.appendChild(tr);
    });
  }

  function renderAlerts(data) {
    const container = document.getElementById('alertsPanel');
    container.innerHTML = '';
    const alerts = data.alerts || {};
    const overdue = alerts.overdue ?? 0;
    const nearBreach = alerts.near_breach ?? 0;
    const critical = alerts.critical_priority ?? 0;

    const row = (type, icon, title, sub, count) => `
      <div class="alert-row ${type}">
        <div class="alert-icon"><i class="bi ${icon}"></i></div>
        <div>
          <div class="alert-title">${title}</div>
          <div class="alert-sub">${sub}</div>
        </div>
        <div class="alert-count">${count}</div>
      </div>`;

    container.insertAdjacentHTML('beforeend', row('danger', 'bi-exclamation-octagon-fill', 'Overdue Tickets', overdue === 1 ? '1 ticket is overdue SLA' : `${overdue} tickets are overdue SLA`, overdue));
    container.insertAdjacentHTML('beforeend', row('warning', 'bi-exclamation-triangle-fill', 'Near SLA Breach', nearBreach === 1 ? '1 ticket is near SLA breach' : `${nearBreach} tickets are near SLA breach`, nearBreach));
    container.insertAdjacentHTML('beforeend', row('info', 'bi-flag-fill', 'Critical Priority Open', critical === 1 ? '1 critical priority ticket is open' : `${critical} critical priority tickets are open`, critical));

    if (!overdue && !nearBreach && !critical) {
      container.insertAdjacentHTML('beforeend', '<div class="alert-row ok"><i class="bi bi-check-circle-fill me-2"></i> All systems normal</div>');
    }
  }

  async function fetchJson(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  }

  async function load() {
    if (isLoading) return;
    showLoading();
    try {
      // Main payload (30-day window covers overview, priority, categories, agents, recent, sla, modules, alerts, and the 30-day trend)
      const [main, weekly] = await Promise.all([
        fetchJson(`${API}?days=30`),
        fetchJson(`${API}?days=7`)
      ]);

      setKpis(main);
      setModules(main);
      renderTicketStatus(main);
      renderWeekly(weekly.trend || { labels: [], data: [] });
      renderSlaGauge(main);
      renderTrend(main.trend || { labels: [], data: [] });
      renderPriority(main);
      renderStatusBar(main);
      renderAgents(main);
      renderRecent(main);
      renderAlerts(main);

      hideLoading();
    } catch (err) {
      console.error('Dashboard error:', err);
      hideLoading();
      showToast('Unable to load dashboard data: ' + err.message, 'danger');
    }
  }

  function setupRefresh() {
    const btn = el('#refreshBtn');
    if (btn) btn.addEventListener('click', load);
    refreshTimer = setInterval(load, REFRESH_INTERVAL);
  }

  document.addEventListener('DOMContentLoaded', () => {
    setTodayDate();
    load();
    setupRefresh();
  });

  window.addEventListener('unload', () => clearInterval(refreshTimer));
})();
