const slaApi = {
  dataUrl: '/sla/data',
  storeUrl: '/sla/rules',
  exportUrl: '/sla/export',
  downloadUrl: (filename) => `/sla/export/download/${filename}`,
};

const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content;

function formatMinutes(minutes) {
  if (!minutes) return '0h';
  const hours = Math.floor(minutes / 60);
  const remainder = minutes % 60;
  return remainder ? `${hours}h ${remainder}m` : `${hours}h`;
}

function buildRequest(url, method = 'GET', data = null) {
  const options = { method, headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } };
  if (data) {
    options.headers['Content-Type'] = 'application/json';
    options.body = JSON.stringify(data);
  }
  return fetch(url, options).then((res) => res.json());
}

function toggleModal(selector, active = true) {
  document.querySelector(selector)?.classList.toggle('active', active);
}

function setActiveRange(filter) {
  document.querySelectorAll('.sla-range-button').forEach((button) => {
    button.classList.toggle('active', button.dataset.range === filter);
  });
}

function showToast(message) {
  const toast = document.getElementById('sla-toast');
  if (!toast) return;
  toast.textContent = message;
  toast.classList.add('active');
  setTimeout(() => toast.classList.remove('active'), 3500);
}

function getDateRange() {
  const active = document.querySelector('.sla-range-button.active');
  const range = active?.dataset.range || 'last7';
  const from = document.getElementById('range-from').value;
  const to = document.getElementById('range-to').value;
  return { range, from, to };
}

function updateDateInputs(from, to) {
  document.getElementById('range-from').value = from;
  document.getElementById('range-to').value = to;
}

function renderKpis(kpis) {
  document.getElementById('on-track-count').textContent = kpis.on_track;
  document.getElementById('at-risk-count').textContent = kpis.at_risk;
  document.getElementById('breached-count').textContent = kpis.breached;
  const overallEl = document.getElementById('overall-percent');
  if (overallEl) overallEl.textContent = `${kpis.overall}%`;
  document.getElementById('avg-resolution-value').textContent = kpis.avg_resolution;
  document.getElementById('avg-resolution-goal').textContent = kpis.resolution_goal;
  document.getElementById('avg-response-value').textContent = kpis.avg_response;
  document.getElementById('avg-response-goal').textContent = kpis.response_goal;
  document.getElementById('overall-circle-label').textContent = `${kpis.overall}%`;
}

function renderRulesTable(rules) {
  const tbody = document.getElementById('sla-rules-table-body');
  tbody.innerHTML = '';
  rules.forEach((rule) => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td><span class="sla-pill ${rule.priority.toLowerCase()}">${rule.priority}</span></td>
      <td>${rule.name}</td>
      <td>${rule.response}</td>
      <td>${rule.resolution}</td>
      <td><span class="sla-pill ${rule.active ? 'active' : 'inactive'}">${rule.status}</span></td>
      <td class="sla-actions">
        <button type="button" class="sla-button secondary" data-action="edit" data-id="${rule.id}">Edit</button>
        <button type="button" class="sla-button secondary" data-action="delete" data-id="${rule.id}">Delete</button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

function renderTrend(chartData) {
  const canvas = document.getElementById('trend-chart');
  const ctx = canvas.getContext('2d');

  // no-data handling
  const hasData = Array.isArray(chartData.total) && chartData.total.some((v) => v > 0);
  if (!hasData) {
    if (window.slaTrendChart) window.slaTrendChart.destroy();
    ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);
    // draw friendly message
    ctx.save();
    ctx.fillStyle = '#94a3b8';
    ctx.font = '16px system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial';
    ctx.textAlign = 'center';
    ctx.fillText('No Data Available', canvas.clientWidth / 2, canvas.clientHeight / 2);
    ctx.restore();
    return;
  }

  const labels = chartData.labels;
  const data = chartData.compliance.map((v) => v === null ? 0 : v);

  // If a chart already exists, update its data and avoid recreating DOM or canvas
  if (window.slaTrendChart) {
    window.slaTrendChart.data.labels = labels;
    window.slaTrendChart.data.datasets[0].data = data;
    // attach the latest chartData for tooltip/plugin access
    window.slaTrendChart._slaChartData = chartData;
    window.slaTrendChart.update();
    return;
  }

  // create gradient using layout size to avoid resize observer feedback loops
  const gradient = ctx.createLinearGradient(0, 0, 0, canvas.clientHeight);
  gradient.addColorStop(0, 'rgba(62,169,110,0.18)');
  gradient.addColorStop(1, 'rgba(62,169,110,0.02)');

  // plugin to draw percentage labels above points
  const percentLabelPlugin = {
    id: 'percentLabelPlugin',
    afterDatasetsDraw(chart) {
      const { ctx } = chart;
      chart.data.datasets.forEach((dataset, datasetIndex) => {
        const meta = chart.getDatasetMeta(datasetIndex);
        meta.data.forEach((point, i) => {
          const value = chart.data.datasets[datasetIndex].data[i];
          if (value === null) return;
          ctx.save();
          ctx.fillStyle = '#065f46';
          ctx.font = '600 12px system-ui, -apple-system, "Segoe UI", Roboto';
          ctx.textAlign = 'center';
          ctx.fillText(value + '%', point.x, point.y - 12);
          ctx.restore();
        });
      });
    }
  };

  window.slaTrendChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'SLA Compliance',
        data,
        borderColor: '#3EA96E',
        backgroundColor: gradient,
        fill: true,
        tension: 0.36,
        pointRadius: 4,
        pointBackgroundColor: '#ffffff',
        pointBorderColor: '#3EA96E',
        pointBorderWidth: 2,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label(context) {
              const chart = context.chart;
              const cd = chart._slaChartData || chartData;
              const idx = context.dataIndex;
              const date = cd.labels[idx] ?? '';
              const percent = cd.compliance[idx];
              const onTrack = cd.onTrack[idx] ?? 0;
              const total = cd.total[idx] ?? 0;
              return `${date}: ${percent ?? 0}% — ${onTrack} / ${total}`;
            }
          }
        }
      },
      interaction: { mode: 'nearest', intersect: false },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#475569' } },
        y: { grid: { color: 'rgba(15,23,42,0.08)' }, beginAtZero: true, ticks: { color: '#475569' }, suggestedMax: 100 },
      },
      animation: { duration: 700, easing: 'cubicBezier(.2,.8,.2,1)' },
    },
    plugins: [percentLabelPlugin],
  });
}

function renderBreakdown(chartData) {
  const ctx = document.getElementById('breakdown-chart').getContext('2d');
  // update existing chart if present to avoid recreating canvas and triggering layout changes
  if (window.slaBreakdownChart) {
    window.slaBreakdownChart.data.labels = chartData.labels;
    window.slaBreakdownChart.data.datasets[0].data = chartData.values;
    window.slaBreakdownChart.data.datasets[0].backgroundColor = chartData.colors;
    window.slaBreakdownChart.update();
    return;
  }

  window.slaBreakdownChart = new Chart(ctx, {
    type: 'doughnut',
    data: { labels: chartData.labels, datasets: [{ data: chartData.values, backgroundColor: chartData.colors }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
  });
}

function renderCompliance(percent) {
  const ctx = document.getElementById('compliance-circle').getContext('2d');
  // update existing chart to avoid recreating canvas
  if (window.slaComplianceChart) {
    window.slaComplianceChart.data.datasets[0].data = [percent, 100 - percent];
    window.slaComplianceChart.update();
    return;
  }

  window.slaComplianceChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Compliant', 'Remaining'],
      datasets: [{
        data: [percent, 100 - percent],
        backgroundColor: ['#3EA96E', '#E2E8F0'],
        borderWidth: 0,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '78%',
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
    },
  });
}

function refreshDashboard() {
  const range = getDateRange();
  buildRequest(`${slaApi.dataUrl}?range=${range.range}&from=${range.from}&to=${range.to}`)
    .then((data) => {
      if (data.error) {
        showToast(data.error);
        return;
      }
      renderKpis(data.kpis);
      renderTrend(data.trend);
      renderBreakdown(data.breakdown);
      renderCompliance(data.kpis.overall);
      renderRulesTable(data.rules);
    })
    .catch(() => showToast('Unable to refresh SLA analytics.'));
}

function openRuleModal(rule = null) {
  const modal = document.getElementById('sla-rule-modal');
  const form = document.getElementById('sla-rule-form');
  form.reset();
  form.dataset.method = 'POST';
  form.dataset.id = '';
  document.getElementById('modal-title').textContent = 'Add New SLA Rule';
  document.getElementById('submit-button').textContent = 'Save Rule';
  document.getElementById('rule-active').checked = true;

  if (rule) {
    form.dataset.method = 'PUT';
    form.dataset.id = rule.id;
    document.getElementById('modal-title').textContent = 'Edit SLA Rule';
    document.getElementById('submit-button').textContent = 'Update Rule';
    document.getElementById('rule-priority').value = rule.priority;
    document.getElementById('rule-name').value = rule.name;
    document.getElementById('rule-description').value = rule.description || '';
    document.getElementById('rule-response-hours').value = rule.response_hours;
    document.getElementById('rule-response-minutes').value = rule.response_minutes;
    document.getElementById('rule-resolution-hours').value = rule.resolution_hours;
    document.getElementById('rule-resolution-minutes').value = rule.resolution_minutes;
    document.getElementById('rule-active').checked = rule.active;
  }

  toggleModal('#sla-rule-backdrop', true);
}

function closeModal(selector) {
  toggleModal(selector, false);
}

function submitRuleForm(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const method = form.dataset.method;
  const id = form.dataset.id;
  const url = method === 'POST' ? slaApi.storeUrl : `/sla/rules/${id}`;
  const data = {
    priority: form.priority.value,
    name: form.name.value,
    description: form.description.value,
    response_hours: Number(form.response_hours.value),
    response_minutes: Number(form.response_minutes.value),
    resolution_hours: Number(form.resolution_hours.value),
    resolution_minutes: Number(form.resolution_minutes.value),
    active: form.active.checked ? 1 : 0,
  };

  buildRequest(url, method, data)
    .then((result) => {
      if (result.errors) {
        showToast(Object.values(result.errors).flat().join(' '));
        return;
      }
      showToast(result.message || 'Rule saved successfully.');
      closeModal('#sla-rule-backdrop');
      refreshDashboard();
    })
    .catch(() => showToast('Unable to save rule.'));
}

function handleTableAction(event) {
  const button = event.target.closest('button[data-action]');
  if (!button) return;

  const action = button.dataset.action;
  const id = button.dataset.id;

  if (action === 'delete') {
    Swal.fire({
      title: 'Delete SLA Rule?',
      text: 'This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Delete',
      cancelButtonText: 'Cancel',
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`/sla/rules/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' } })
          .then((res) => res.json())
          .then((body) => {
            showToast(body.message || 'Rule deleted');
            refreshDashboard();
          })
          .catch(() => showToast('Could not delete rule.'));
      }
    });

    return;
  }

  if (action === 'edit') {
    fetch(`/sla/rules/${id}/json`, { headers: { Accept: 'application/json' } })
      .then((res) => res.json())
      .then((body) => {
        if (body.error) {
          showToast(body.error);
          return;
        }
        openRuleModal(body.rule);
      })
      .catch(() => showToast('Unable to load rule details.'));
  }
}

function handleReportExport(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const data = {
    report_type: form.report_type.value,
    format: form.format.value,
    range: document.querySelector('.sla-range-button.active')?.dataset.range || 'last7',
    from: document.getElementById('range-from').value,
    to: document.getElementById('range-to').value,
  };

  buildRequest(slaApi.exportUrl, 'POST', data)
    .then((result) => {
      if (result.error) {
        showToast(result.error);
        return;
      }
      closeModal('#sla-export-backdrop');
      Swal.fire({
        icon: 'success',
        title: 'Report Exported Successfully',
        text: 'Your SLA report is ready to download.',
        confirmButtonText: 'Download Report',
      }).then(() => {
        window.location.href = slaApi.downloadUrl(result.filename);
      });
    })
    .catch(() => showToast('Export request failed.'));
}

function bindEvents() {
  document.querySelectorAll('.sla-range-button').forEach((button) => {
    button.addEventListener('click', () => {
      setActiveRange(button.dataset.range);
      if (button.dataset.from && button.dataset.to) {
        updateDateInputs(button.dataset.from, button.dataset.to);
      }
      refreshDashboard();
    });
  });

  document.getElementById('add-rule-button')?.addEventListener('click', () => openRuleModal());
  document.getElementById('export-report-button')?.addEventListener('click', () => toggleModal('#sla-export-backdrop', true));
  document.getElementById('modal-close-button')?.addEventListener('click', () => closeModal('#sla-rule-backdrop'));
  document.getElementById('export-modal-close')?.addEventListener('click', () => closeModal('#sla-export-backdrop'));
  document.getElementById('sla-rule-form')?.addEventListener('submit', submitRuleForm);
  document.getElementById('sla-export-form')?.addEventListener('submit', handleReportExport);
  document.getElementById('sla-rules-table-body')?.addEventListener('click', handleTableAction);
  document.getElementById('range-from')?.addEventListener('change', refreshDashboard);
  document.getElementById('range-to')?.addEventListener('change', refreshDashboard);
}

document.addEventListener('DOMContentLoaded', () => {
  bindEvents();
  refreshDashboard();
});
