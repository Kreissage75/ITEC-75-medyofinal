<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SLA Report</title>
  <style>
    body { font-family: Arial, sans-serif; color: #1f2937; margin: 24px; }
    h1 { font-size: 24px; margin-bottom: 4px; }
    h2 { font-size: 16px; margin: 20px 0 8px; }
    p { margin: 0 0 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #cbd5e1; padding: 8px 10px; font-size: 12px; }
    th { background: #f8fafc; text-align: left; }
    .summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 12px; margin-top: 16px; }
    .summary-card { padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; background: #ffffff; }
    .summary-card strong { display: block; margin-top: 6px; font-size: 16px; }
  </style>
</head>
<body>
  <h1>SLA Report</h1>
  <p>Period: {{ $from->toDateString() }} to {{ $to->toDateString() }}</p>

  <div class="summary-grid">
    <div class="summary-card"><span>Overall Compliance</span><strong>{{ $kpis['overall'] }}%</strong></div>
    <div class="summary-card"><span>On Track</span><strong>{{ $kpis['on_track'] }}</strong></div>
    <div class="summary-card"><span>At Risk</span><strong>{{ $kpis['at_risk'] }}</strong></div>
    <div class="summary-card"><span>Breached</span><strong>{{ $kpis['breached'] }}</strong></div>
    <div class="summary-card"><span>Avg Resolution</span><strong>{{ $kpis['avg_resolution'] }}</strong></div>
    <div class="summary-card"><span>Avg Response</span><strong>{{ $kpis['avg_response'] }}</strong></div>
  </div>

  <h2>SLA Rules & Policies</h2>
  <table>
    <thead>
      <tr><th>Priority</th><th>Rule</th><th>Response Goal</th><th>Resolution Goal</th><th>Status</th></tr>
    </thead>
    <tbody>
      @foreach($rules as $rule)
        <tr>
          <td>{{ $rule['priority'] }}</td>
          <td>{{ $rule['name'] }}</td>
          <td>{{ $rule['response'] }}</td>
          <td>{{ $rule['resolution'] }}</td>
          <td>{{ $rule['status'] }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
