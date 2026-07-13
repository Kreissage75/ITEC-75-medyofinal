<?php

namespace App\Http\Controllers;

use App\Models\Customer;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->paginate(10);

        return view('customers.index', compact('customers'));
    }

    public function show(Customer $customer)
    {
        $tickets = $customer->tickets()->latest()->get();

        if (request()->wantsJson()) {
            $formattedTickets = $tickets->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'code' => $ticket->code,
                    'subject' => $ticket->subject,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'priority_class' => $ticket->priorityPillClass(),
                    'status_class' => $ticket->statusPillClass(),
                    'time_ago' => $ticket->created_at->diffForHumans(),
                ];
            });

            $totalCount = $tickets->count();
            $resolvedCount = $tickets->where('status', 'resolved')->count();
            $pendingCount = $totalCount - $resolvedCount; // open and pending combined

            return response()->json([
                'id' => $customer->id,
                'name' => $customer->name,
                'initial' => strtoupper(substr($customer->name, 0, 1)),
                'email' => $customer->email,
                'phone' => $customer->phone ?? 'N/A',
                'company' => $customer->company ?? 'N/A',
                'status' => $customer->status,
                'tickets' => $formattedTickets,
                'stats' => [
                    'all' => $totalCount,
                    'pending' => $pendingCount,
                    'resolved' => $resolvedCount,
                ]
            ]);
        }

        return view('customers.show', compact('customer', 'tickets'));
    }
}
