<?php

namespace App\Http\Controllers;

use App\Models\SlaRule;
use App\Services\SlaMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SlaController extends Controller
{
    public function index(Request $request, SlaMetricsService $metrics)
    {
        [$from, $to] = $this->parseDateRange($request);
        $kpis = $metrics->getKpis($from, $to);
        $trend = $metrics->getTrend($from, $to);
        $breakdown = $metrics->getBreakdown($from, $to);
        $rules = SlaRule::orderByRaw("CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 WHEN 'General' THEN 4 ELSE 5 END")->get();
        $range = $request->query('range', 'last7');

        return view('sla.index', compact('rules', 'kpis', 'trend', 'breakdown', 'from', 'to', 'range'));
    }

    public function data(Request $request, SlaMetricsService $metrics)
    {
        [$from, $to] = $this->parseDateRange($request);

        return response()->json([
            'kpis' => $metrics->getKpis($from, $to),
            'trend' => $metrics->getTrend($from, $to),
            'breakdown' => $metrics->getBreakdown($from, $to),
            'rules' => $metrics->getRuleTableData(),
        ]);
    }

    public function storeRule(Request $request)
    {
        $data = $this->validatedRule($request);
        $rule = SlaRule::create($data);

        return response()->json(["message" => 'SLA rule created successfully.', 'rule' => $rule], 201);
    }

    public function updateRule(Request $request, SlaRule $rule)
    {
        $data = $this->validatedRule($request);
        $rule->update($data);

        return response()->json(["message" => 'SLA rule updated successfully.', 'rule' => $rule]);
    }

    public function destroyRule(SlaRule $rule)
    {
        $rule->delete();

        return response()->json(['message' => 'SLA rule deleted successfully.']);
    }

    public function ruleJson(SlaRule $rule)
    {
        return response()->json(['rule' => $rule]);
    }

    public function export(Request $request, SlaMetricsService $metrics)
    {
        $request->validate([
            'report_type' => 'required|string|max:255',
            'format' => 'required|in:pdf,excel,csv',
            'range' => 'required|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        [$from, $to] = $this->parseDateRange($request);
        $kpis = $metrics->getKpis($from, $to);
        $trend = $metrics->getTrend($from, $to);
        $breakdown = $metrics->getBreakdown($from, $to);
        $rules = $metrics->getRuleTableData();

        $payload = compact('from', 'to', 'kpis', 'trend', 'breakdown', 'rules');
        $format = $request->input('format');
        $fileExtension = $format === 'excel' ? 'xls' : $format;
        $filename = 'sla-report-' . Str::slug($request->input('report_type')) . '-' . now()->format('YmdHis') . '.' . $fileExtension;
        $path = "reports/{$filename}";

        if ($format === 'csv') {
            Storage::put($path, $this->buildCsv($payload));
        } elseif ($format === 'excel') {
            Storage::put($path, $this->buildExcel($payload));
        } else {
            Storage::put($path, $this->buildPdf($payload));
        }

        return response()->json(['message' => 'SLA report created successfully.', 'filename' => $filename]);
    }

    public function downloadExport(string $filename)
    {
        $path = "reports/{$filename}";

        if (! Storage::exists($path)) {
            abort(404);
        }

        return Storage::download($path);
    }

    private function validatedRule(Request $request): array
    {
        return $request->validate([
            'priority' => 'required|string|in:High,Medium,Low,General',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'response_hours' => 'required|integer|min:0',
            'response_minutes' => 'required|integer|min:0',
            'resolution_hours' => 'required|integer|min:0',
            'resolution_minutes' => 'required|integer|min:0',
            'active' => 'nullable|boolean',
        ]);
    }

    private function parseDateRange(Request $request): array
    {
        $range = $request->query('range', 'last7');
        $from = $request->query('from');
        $to = $request->query('to');
        $today = Carbon::today();

        return match ($range) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'last7' => [Carbon::today()->subDays(6)->startOfDay(), Carbon::today()->endOfDay()],
            'last30' => [Carbon::today()->subDays(29)->startOfDay(), Carbon::today()->endOfDay()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfDay()],
            'last_month' => [Carbon::now()->subMonthNoOverflow()->startOfMonth(), Carbon::now()->subMonthNoOverflow()->endOfMonth()],
            default => $this->customRange($from, $to),
        };
    }

    private function customRange(?string $from, ?string $to): array
    {
        $start = $from ? Carbon::parse($from)->startOfDay() : Carbon::today()->subDays(6)->startOfDay();
        $end = $to ? Carbon::parse($to)->endOfDay() : Carbon::today()->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function buildCsv(array $payload): string
    {
        $lines = ['Section,Metric,Value'];
        $lines[] = ["Period", "{$payload['from']->toDateString()} to {$payload['to']->toDateString()}"];
        $lines[] = ["Overall SLA Compliance", $payload['kpis']['overall'] . '%'];
        $lines[] = ["On Track", $payload['kpis']['on_track']];
        $lines[] = ["At Risk", $payload['kpis']['at_risk']];
        $lines[] = ["Breached", $payload['kpis']['breached']];
        $lines[] = ["Average Resolution Time", $payload['kpis']['avg_resolution']];
        $lines[] = ["Average Response Time", $payload['kpis']['avg_response']];
        $csv = array_map(fn ($row) => implode(',', array_map([$this, 'escapeCsv'], $row)), $lines);
        $csv[] = '';
        $csv[] = 'SLA Rules,Priority,Response Time Goal,Resolution Time Goal,Status';

        foreach ($payload['rules'] as $rule) {
            $csv[] = implode(',', array_map([$this, 'escapeCsv'], [
                $rule['name'],
                $rule['priority'],
                $rule['response'],
                $rule['resolution'],
                $rule['status'],
            ]));
        }

        return implode("\r\n", $csv);
    }

    private function buildExcel(array $payload): string
    {
        return $this->buildCsv($payload);
    }

    private function buildPdf(array $payload): string
    {
        if (class_exists(\Dompdf\Dompdf::class)) {
            $html = view('sla.report', $payload)->render();
            $pdf = new \Dompdf\Dompdf();
            $pdf->loadHtml($html);
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();

            return $pdf->output();
        }

        return $this->buildCsv($payload);
    }

    private function escapeCsv(string $value): string
    {
        $escaped = str_replace('"', '""', $value);

        return '"' . $escaped . '"';
    }
}
