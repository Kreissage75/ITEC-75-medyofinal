@extends('layouts.app')

@section('title', 'Ticket Management System')

@section('content')
  <div class="card">
    <div class="card-head">
      <div class="card-title">TICKET MANAGEMENT SYSTEM</div>
      <a href="{{ route('tickets.create') }}" class="btn green">+ New Ticket</a>
    </div>

    <form method="GET" style="display:flex;gap:10px;margin-bottom:14px;">
      <select name="status" class="btn" onchange="this.form.submit()">
        <option value="">All statuses</option>
        @foreach(['open','pending','resolved'] as $s)
          <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <select name="priority" class="btn" onchange="this.form.submit()">
        <option value="">All priorities</option>
        @foreach(['High','Medium','Low','General'] as $p)
          <option value="{{ $p }}" @selected(request('priority')===$p)>{{ $p }}</option>
        @endforeach
      </select>
    </form>

    <table class="data-table">
      <thead>
        <tr><th>Code</th><th>Subject</th><th>Customer</th><th>Priority</th><th>Status</th><th>Assigned</th><th></th></tr>
      </thead>
      <tbody>
        @forelse($tickets as $ticket)
          <tr>
            <td><a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->code }}</a></td>
            <td>{{ $ticket->subject }}</td>
            <td>{{ $ticket->customer_name }}</td>
            <td><span class="pill {{ $ticket->priorityPillClass() }}">{{ $ticket->priority }}</span></td>
            <td><span class="pill {{ $ticket->statusPillClass() }}">{{ ucfirst($ticket->status) }}</span></td>
            <td>{{ $ticket->assigned_to ?? '—' }}</td>
            <td><a href="{{ route('tickets.edit', $ticket) }}">Edit</a></td>
          </tr>
        @empty
          <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px;">No tickets to show yet.</td></tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:14px;">{{ $tickets->links() }}</div>
  </div>
@endsection
