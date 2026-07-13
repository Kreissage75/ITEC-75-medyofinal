<?php

namespace App\Services;

use App\Models\SlaRule;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlaMetricsService
{
    public function __construct(protected ?Carbon $now = null)
    {
        $this->now = $now ?: now();
    }

    public function getRules(): Collection
    {
        // Use DB-agnostic CASE ordering to support sqlite/mysql
        return SlaRule::orderByRaw(
            "CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 WHEN 'General' THEN 4 ELSE 5 END"
        )->get();
    }

    public function getRuleMap(): Collection
    {
        return $this->getRules()->keyBy('priority');
    }

    public function getKpis(Carbon $from, Carbon $to): array
    {
        $tickets = Ticket::whereBetween('created_at', [$from, $to])->get();
        $counts = [
            'on_track' => 0,
            'at_risk' => 0,
            'breached' => 0,
        ];

        $rules = $this->getRuleMap();

        foreach ($tickets as $ticket) {
            $status = $this->ticketSlaStatus($ticket, $rules);
            $counts[$status]++;
        }

        $total = max(1, array_sum($counts));
        $percent = round($counts['on_track'] / $total * 100);

        $avgResolution = $this->averageResolutionTime($from, $to);
        $avgResponse = $this->averageResponseTime($from, $to);

        return [
            'on_track' => $counts['on_track'],
            'at_risk' => $counts['at_risk'],
            'breached' => $counts['breached'],
            'overall' => $percent,
            'avg_resolution' => $this->formatMinutes($avgResolution),
            'avg_response' => $this->formatMinutes($avgResponse),
            'resolution_goal' => $this->formatMinutes($this->averageResolutionGoal()),
            'response_goal' => $this->formatMinutes($this->averageResponseGoal()),
        ];
    }

    public function getTrend(Carbon $from, Carbon $to): array
    {
        $labels = [];
        $compliance = [];
        $onTrack = [];
        $total = [];

        $rules = $this->getRuleMap();
        $current = $from->copy();

        while ($current->lte($to)) {
            $labels[] = $current->format('M j');
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();
            $dailyTickets = Ticket::whereBetween('created_at', [$dayStart, $dayEnd])->get();

            $dailyOnTrack = 0;
            foreach ($dailyTickets as $ticket) {
                $status = $this->ticketSlaStatus($ticket, $rules);
                if ($status === 'on_track') $dailyOnTrack++;
            }

            $dailyTotal = $dailyTickets->count();
            $dailyPercent = $dailyTotal ? (int) round($dailyOnTrack / $dailyTotal * 100) : null;

            $onTrack[] = $dailyOnTrack;
            $total[] = $dailyTotal;
            $compliance[] = $dailyPercent;

            $current->addDay();
        }

        return compact('labels', 'compliance', 'onTrack', 'total');
    }

    public function getBreakdown(Carbon $from, Carbon $to): array
    {
        $kpis = $this->getKpis($from, $to);

        return [
            'labels' => ['On Track', 'At Risk', 'Breached'],
            'values' => [$kpis['on_track'], $kpis['at_risk'], $kpis['breached']],
            'colors' => ['#3EA96E', '#F4A261', '#EF4B47'],
        ];
    }

    public function getOverallCompliance(Carbon $from, Carbon $to): int
    {
        return $this->getKpis($from, $to)['overall'];
    }

    public function getRuleTableData(): array
    {
        return $this->getRules()->map(function (SlaRule $rule) {
            return [
                'id' => $rule->id,
                'priority' => $rule->priority,
                'name' => $rule->name,
                'description' => $rule->description,
                'response' => $rule->formatted('response'),
                'resolution' => $rule->formatted('resolution'),
                'status' => $rule->active ? 'Active' : 'Inactive',
                'active' => $rule->active,
            ];
        })->toArray();
    }

    public function ticketSlaStatus(Ticket $ticket, Collection $rules): string
    {
        $rule = $rules->get($ticket->priority) ?? $rules->get('General');

        if (! $rule) {
            return 'on_track';
        }

        $target = $rule->resolutionTargetMinutes();
        $elapsed = $ticket->ageMinutes($this->now);

        if ($ticket->status === 'resolved') {
            $resolution = $ticket->resolutionTimeMinutes();

            return $resolution !== null && $resolution <= $target ? 'on_track' : 'breached';
        }

        if ($elapsed >= $target) {
            return 'breached';
        }

        if ($elapsed >= max(1, (int) ($target * 0.75))) {
            return 'at_risk';
        }

        return 'on_track';
    }

    public function averageResolutionTime(Carbon $from, Carbon $to): int
    {
        $resolved = Ticket::where('status', 'resolved')
            ->whereBetween('updated_at', [$from, $to])
            ->get()
            ->map(fn (Ticket $ticket) => $ticket->resolutionTimeMinutes())
            ->filter()
            ->toArray();

        return $resolved ? (int) round(array_sum($resolved) / count($resolved)) : 0;
    }

    public function averageResponseTime(Carbon $from, Carbon $to): int
    {
        $responded = Ticket::where('status', '!=', 'open')
            ->whereBetween('updated_at', [$from, $to])
            ->get()
            ->map(fn (Ticket $ticket) => $ticket->responseTimeMinutes())
            ->filter()
            ->toArray();

        return $responded ? (int) round(array_sum($responded) / count($responded)) : 0;
    }

    public function averageResolutionGoal(): int
    {
        $rules = $this->getRules()->filter(fn (SlaRule $rule) => $rule->active);

        return $rules->count() ? (int) round($rules->avg(fn (SlaRule $rule) => $rule->resolutionTargetMinutes())) : 0;
    }

    public function averageResponseGoal(): int
    {
        $rules = $this->getRules()->filter(fn (SlaRule $rule) => $rule->active);

        return $rules->count() ? (int) round($rules->avg(fn (SlaRule $rule) => $rule->responseTargetMinutes())) : 0;
    }

    public function formatMinutes(int $minutes): string
    {
        if ($minutes === 0) {
            return '0h';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return $remaining ? "{$hours}h {$remaining}m" : "{$hours}h";
    }
}
