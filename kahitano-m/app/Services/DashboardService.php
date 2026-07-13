<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Customer;
use App\Models\Ticket;
use App\Services\SlaMetricsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(protected SlaMetricsService $sla)
    {
    }

    public function overview(): array
    {
        $from = now()->copy()->subDays(30);
        $to = now();

        $kpis = $this->sla->getKpis($from, $to);

        $open = Ticket::where('status', 'open')->count();
        $pending = Ticket::where('status', 'pending')->count();
        $resolved = Ticket::where('status', 'resolved')->count();

        // average response in minutes across last 30 days
        $avgResponse = $kpis['avg_response'] ?? '0h';

        // due today: tickets not resolved whose SLA deadline falls within today
        $rules = $this->sla->getRuleMap();
        $todayStart = Carbon::now()->startOfDay();
        $todayEnd = Carbon::now()->endOfDay();

        $dueToday = Ticket::where('status', '!=', 'resolved')
            ->get()
            ->filter(function (Ticket $ticket) use ($rules, $todayStart, $todayEnd) {
                $rule = $rules->get($ticket->priority) ?? $rules->get('General');
                if (! $rule) return false;
                $deadline = $ticket->created_at->copy()->addMinutes($rule->resolutionTargetMinutes());
                return $deadline->between($todayStart, $todayEnd);
            })->count();

        return [
            'open' => $open,
            'pending' => $pending,
            'resolved' => $resolved,
            'avg_response' => $avgResponse,
            'sla_due_today' => $dueToday,
            'overall_sla' => $kpis['overall'] ?? 0,
        ];
    }

    public function priorityDistribution(): array
    {
        $labels = ['High', 'Medium', 'Low', 'General'];
        $values = [];
        foreach ($labels as $label) {
            $values[] = Ticket::where('priority', $label)->count();
        }

        return compact('labels', 'values');
    }

    public function ticketsOverTime(int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $data = [];
        $labels = [];

        $current = $start->copy();
        while ($current->lte(now())) {
            $labels[] = $current->format('M j');
            $count = Ticket::whereBetween('created_at', [$current->copy()->startOfDay(), $current->copy()->endOfDay()])->count();
            $data[] = $count;
            $current->addDay();
        }

        return compact('labels', 'data');
    }

    public function agentPerformance(int $limit = 6): array
    {
        $tickets = Ticket::whereNotNull('assigned_to')->get();

        $grouped = $tickets->groupBy('assigned_to')->map(function (Collection $group, $agent) {
            $assigned = $group->count();
            $resolved = $group->where('status', 'resolved');
            $resolvedCount = $resolved->count();
            $avgResolution = $resolved->map->resolutionTimeMinutes()->filter()->count()
                ? (int) round($resolved->map->resolutionTimeMinutes()->filter()->avg())
                : 0;

            // SLA success rate: resolved on time / resolved
            $rules = $this->sla->getRuleMap();
            $resolvedOnTime = 0;
            foreach ($resolved as $ticket) {
                $status = $this->sla->ticketSlaStatus($ticket, $rules);
                if ($status === 'on_track') $resolvedOnTime++;
            }

            $slaRate = $resolvedCount ? (int) round($resolvedOnTime / $resolvedCount * 100) : null;

            return [
                'agent' => $agent,
                'assigned' => $assigned,
                'resolved' => $resolvedCount,
                'avg_resolution' => $avgResolution,
                'sla_rate' => $slaRate,
            ];
        })->sortByDesc(fn ($item) => $item['assigned'])->values()->take($limit)->toArray();

        return $grouped;
    }

    public function recentTickets(int $limit = 6): array
    {
        return Ticket::latest()->take($limit)->get(['id', 'code', 'subject', 'status', 'priority', 'assigned_to', 'created_at'])->toArray();
    }

    public function slaOverview(): array
    {
        $from = now()->subDays(30);
        $to = now();
        $kpis = $this->sla->getKpis($from, $to);
        $breakdown = $this->sla->getBreakdown($from, $to);

        return compact('kpis', 'breakdown');
    }

    public function ticketCategories(): array
    {
        $statuses = ['open', 'pending', 'resolved'];
        $values = [];
        foreach ($statuses as $status) {
            $values[] = Ticket::where('status', $status)->count();
        }

        return [
            'labels' => ['Open', 'Pending', 'Resolved'],
            'values' => $values,
        ];
    }

    public function moduleMetrics(): array
    {
        $articles = Article::count();
        $articleViews = Article::sum('views') ?? 0;
        $helpfulVotes = Article::sum('helpful_count') ?? 0;
        $customers = Customer::count();

        return [
            'articles' => $articles,
            'faq_views' => $articleViews,
            'searches' => 0,
            'helpful' => $helpfulVotes,
            'unread_messages' => 0,
            'open_conversations' => $customers,
        ];
    }

    public function slaAlerts(): array
    {
        $tickets = Ticket::where('status', '!=', 'resolved')->get();
        $rules = $this->sla->getRuleMap();

        $overdue = 0;
        $nearBreach = 0;

        foreach ($tickets as $ticket) {
            $status = $this->sla->ticketSlaStatus($ticket, $rules);
            if ($status === 'breached') $overdue++;
            elseif ($status === 'at_risk') $nearBreach++;
        }

        $critical = Ticket::where('priority', 'High')->count();

        return [
            'overdue' => $overdue,
            'near_breach' => $nearBreach,
            'critical_priority' => $critical,
        ];
    }
}
