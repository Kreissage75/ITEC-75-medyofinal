@extends('layouts.app')

@section('title', 'Edit Ticket ' . $ticket->code)

@section('content')
  <div class="card" style="max-width:520px;">
    <div class="card-head"><div class="card-title">EDIT {{ $ticket->code }}</div></div>

    <form method="POST" action="{{ route('tickets.update', $ticket) }}" style="display:flex;flex-direction:column;gap:10px;">
      @csrf @method('PATCH')
      <label>Subject
        <input type="text" name="subject" value="{{ old('subject', $ticket->subject) }}" style="width:100%;padding:8px;">
      </label>
      <label>Priority
        <select name="priority" style="width:100%;padding:8px;">
          @foreach(['High','Medium','Low','General'] as $p)
            <option value="{{ $p }}" @selected($ticket->priority===$p)>{{ $p }}</option>
          @endforeach
        </select>
      </label>
      <label>Status
        <select name="status" style="width:100%;padding:8px;">
          @foreach(['open','pending','resolved'] as $s)
            <option value="{{ $s }}" @selected($ticket->status===$s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </label>
      <label>Assigned to
        <input type="text" name="assigned_to" value="{{ old('assigned_to', $ticket->assigned_to) }}" style="width:100%;padding:8px;">
      </label>
      <label>Description
        <textarea name="description" rows="4" style="width:100%;padding:8px;">{{ old('description', $ticket->description) }}</textarea>
      </label>
      <button type="submit" class="btn green" style="align-self:flex-start;">Save Changes</button>
    </form>
  </div>
@endsection
