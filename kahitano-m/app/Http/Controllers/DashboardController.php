<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ticket;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'open_tickets' => Ticket::where('status', 'open')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'avg_response' => '1h 25m',
            'sla_due_today' => Ticket::where('status', '!=', 'resolved')->count(),
        ];

        $recentTickets = Ticket::latest()->take(3)->get();
        $customerCount = Customer::count();

        return view('dashboard.index', compact('stats', 'recentTickets', 'customerCount'));
    }

    public function data(Request $request, DashboardService $service)
    {
        $days = (int) $request->get('days', 14);

        $payload = [
            'overview' => $service->overview(),
            'priority' => $service->priorityDistribution(),
            'trend' => $service->ticketsOverTime($days),
            'categories' => $service->ticketCategories(),
            'agents' => $service->agentPerformance(6),
            'recent' => $service->recentTickets(6),
            'sla' => $service->slaOverview(),
            'modules' => $service->moduleMetrics(),
            'alerts' => $service->slaAlerts(),
        ];

        return response()->json($payload);
    }
}
