@extends('layouts.app')

@section('title', 'New Ticket')

@section('content')
  <div class="card" style="max-width:520px;">
    <div class="card-head"><div class="card-title">NEW TICKET</div></div>

    @if ($errors->any())
      <div style="color:#a12b2b;font-size:12px;margin-bottom:10px;">
        <ul>@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
      </div>
    @endif

    <form method="POST" action="{{ route('tickets.store') }}" style="display:flex;flex-direction:column;gap:10px;">
      @csrf
      <label>Subject
        <input type="text" name="subject" value="{{ old('subject') }}" style="width:100%;padding:8px;">
      </label>
      <label>Customer name
        <input type="text" name="customer_name" value="{{ old('customer_name') }}" style="width:100%;padding:8px;">
      </label>
      <label>Priority
        <select name="priority" style="width:100%;padding:8px;">
          @foreach(['High','Medium','Low','General'] as $p)
            <option value="{{ $p }}">{{ $p }}</option>
          @endforeach
        </select>
      </label>
      <label>Description
        <textarea name="description" rows="4" style="width:100%;padding:8px;">{{ old('description') }}</textarea>
      </label>
      <button type="submit" class="btn green" style="align-self:flex-start;">Create Ticket</button>
    </form>
  </div>
@endsection
