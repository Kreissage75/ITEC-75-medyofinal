<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->latest()
            ->paginate(10);

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('tickets.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'priority' => 'required|in:High,Medium,Low,General',
            'description' => 'nullable|string',
        ]);

        $data['code'] = 'TCK-' . str_pad((string) (Ticket::max('id') + 1), 4, '0', STR_PAD_LEFT);
        $data['status'] = 'open';

        $ticket = Ticket::create($data);

        return redirect()->route('tickets.show', $ticket)->with('status', 'Ticket created successfully.');
    }

    public function show(Ticket $ticket)
    {
        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket)
    {
        return view('tickets.edit', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:High,Medium,Low,General',
            'status' => 'required|in:open,pending,resolved',
            'assigned_to' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $ticket->update($data);

        return redirect()->route('tickets.show', $ticket)->with('status', 'Ticket updated successfully.');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()->route('tickets.index')->with('status', 'Ticket deleted.');
    }
}
