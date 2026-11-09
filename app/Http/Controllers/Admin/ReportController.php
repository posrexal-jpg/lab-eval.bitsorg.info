<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FormTemplate;
use App\Models\Room;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $service)
    {
        $filters = $this->filters($request);

        return view('admin.reports', [
            'filters'   => $filters,
            'report'    => $service->build($filters),
            'rooms'     => Room::withTrashed()->orderBy('name')->get(),
            'templates' => FormTemplate::orderByDesc('id')->get(),
            'selected'  => Room::withTrashed()->whereIn('id', (array) ($filters['room_id'] ?? []))->pluck('name'),
        ]);
    }

    public function export(Request $request, ReportService $service): StreamedResponse
    {
        $filters = $this->filters($request);
        AuditLog::record('report.exported');

        // Union of columns across all template versions.
        $columns = [];
        foreach (FormTemplate::orderBy('id')->get() as $t) {
            foreach ($t->fields() as $f) {
                $columns[$f['name']] = $f['label'];
            }
        }

        return response()->streamDownload(function () use ($service, $filters, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads accents correctly
            fputcsv($out, array_merge(['ID', 'Submitted', 'Room', 'Form version', 'Average', 'Status'], array_values($columns)));

            $service->query($filters)->with(['room', 'template'])->chunkById(500, function ($rows) use ($out, $columns) {
                foreach ($rows as $ev) {
                    $line = [
                        $ev->id,
                        $ev->created_at->format('Y-m-d H:i'),
                        $ev->room->name,
                        $ev->template->name.' v'.$ev->template->version,
                        $ev->average_score,
                        $ev->status,
                    ];
                    foreach (array_keys($columns) as $name) {
                        $v = $ev->answers[$name] ?? '';
                        $line[] = is_array($v) ? implode('; ', $v) : $v;
                    }
                    // Neutralize spreadsheet formula injection from free-text answers.
                    fputcsv($out, array_map(
                        fn ($c) => is_string($c) && preg_match('/^[=+\-@\t\r]/', $c) ? "'".$c : $c,
                        $line
                    ));
                }
            });
            fclose($out);
        }, 'lab-evaluations-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        // The responses page sends one room_id; the report form sends several.
        if ($request->filled('room_id') && !is_array($request->input('room_id'))) {
            $request->merge(['room_id' => [$request->input('room_id')]]);
        }

        return $request->validate([
            'room_id'       => ['nullable', 'array'],
            'room_id.*'     => ['integer'],
            'template_id'   => ['nullable', 'integer'],
            'from'          => ['nullable', 'date'],
            'to'            => ['nullable', 'date', 'after_or_equal:from'],
            'status'        => ['nullable', 'in:visible,flagged,hidden'],
            'min_score'     => ['nullable', 'numeric', 'between:1,5'],
            'max_score'     => ['nullable', 'numeric', 'between:1,5'],
        ]);
    }
}
