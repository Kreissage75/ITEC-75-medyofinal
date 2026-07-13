@extends('layouts.app')

@section('title', 'Ticket ' . $ticket->code)

@section('content')
  @if (session('status'))
    <div style="background:var(--green-pale);color:var(--green-dark);padding:10px 14px;border-radius:6px;font-size:12.5px;">
      {{ session('status') }}
    </div>
  @endif

  <div class="card">
    <div class="card-head">
      <div class="card-title">TICKET {{ $ticket->code }}</div>
      <div style="display:flex;gap:8px;">
        <span class="pill {{ $ticket->priorityPillClass() }}">{{ $ticket->priority }}</span>
        <span class="pill {{ $ticket->statusPillClass() }}">{{ ucfirst($ticket->status) }}</span>
      </div>
    </div>

    <h2 style="font-size:16px;margin:0 0 10px;">{{ $ticket->subject }}</h2>
    <p style="font-size:12.5px;color:var(--text-muted);margin:0 0 16px;">
      Customer: <strong>{{ $ticket->customer_name }}</strong>
      &middot; Assigned to: <strong>{{ $ticket->assigned_to ?? 'Unassigned' }}</strong>
      &middot; Created {{ $ticket->created_at->diffForHumans() }}
    </p>

    <p style="font-size:13px;line-height:1.6;">{{ $ticket->description ?: 'No description provided.' }}</p>

    <div style="display:flex;gap:10px;margin-top:20px;">
      <a href="{{ route('tickets.edit', $ticket) }}" class="btn green">Edit Ticket</a>
      <a href="{{ route('tickets.index') }}" class="btn">Back to list</a>
      <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" onsubmit="return confirm('Delete this ticket?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn" style="color:#a12b2b;">Delete</button>
      </form>
    </div>
  </div>
@endsection
