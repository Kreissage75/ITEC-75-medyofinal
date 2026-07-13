@extends('layouts.app')

@section('title', $customer->name)

@section('content')
  <div class="card">
    <div class="card-head"><div class="card-title">{{ strtoupper($customer->name) }}</div></div>
    <p style="font-size:12.5px;color:var(--text-muted);">
      {{ $customer->company }} &middot; {{ $customer->email }} &middot; {{ $customer->phone }}
    </p>
  </div>

  <div class="card">
    <div class="card-head"><div class="card-title">TICKET HISTORY</div></div>
    <table class="data-table">
      <thead><tr><th>Code</th><th>Subject</th><th>Priority</th><th>Status</th></tr></thead>
      <tbody>
        @forelse($tickets as $t)
          <tr>
            <td><a href="{{ route('tickets.show', $t) }}">{{ $t->code }}</a></td>
            <td>{{ $t->subject }}</td>
            <td><span class="pill {{ $t->priorityPillClass() }}">{{ $t->priority }}</span></td>
            <td><span class="pill {{ $t->statusPillClass() }}">{{ ucfirst($t->status) }}</span></td>
          </tr>
        @empty
          <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:20px;">No tickets for this customer.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <a href="{{ route('customers.index') }}" class="btn">Back to customer list</a>
@endsection
