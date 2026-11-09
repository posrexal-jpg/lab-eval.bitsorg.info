<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Evaluation;
use App\Models\Room;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        return view('admin.evaluations.index', [
            'evaluations' => Evaluation::with('room')->filter($filters)->latest()->paginate(25)->withQueryString(),
            'rooms'       => Room::withTrashed()->orderBy('name')->get(),
            'filters'     => $filters,
        ]);
    }

    public function show(Evaluation $evaluation)
    {
        $evaluation->load('room', 'template');

        // Group answers by the labels/sections of the template version they were submitted with.
        $groups = [];
        foreach ($evaluation->template->fields() as $f) {
            if (!array_key_exists($f['name'], $evaluation->answers)) {
                continue;
            }
            $value = $evaluation->answers[$f['name']];
            $groups[$f['section'] ?? ($f['rating'] ? 'Ratings' : 'Details')][] = [
                'label'  => $f['label'],
                'value'  => is_array($value) ? implode(', ', $value) : (string) $value,
                'rating' => $f['rating'],
                'long'   => $f['type'] === 'textarea',
            ];
        }

        return view('admin.evaluations.show', compact('evaluation', 'groups'));
    }

    public function status(Request $request, Evaluation $evaluation)
    {
        $data = $request->validate(['status' => ['required', 'in:visible,flagged,hidden']]);
        $evaluation->update($data);
        AuditLog::record('response.'.$data['status'], '#'.$evaluation->id);

        return back()->with('status', 'Response marked as '.$data['status'].'.');
    }

    public function destroy(Evaluation $evaluation)
    {
        AuditLog::record('response.deleted', '#'.$evaluation->id);
        $evaluation->delete();

        return redirect()->route('admin.evaluations.index')->with('status', 'Response deleted.');
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'room_id'   => ['nullable', 'integer'],
            'status'    => ['nullable', 'in:visible,flagged,hidden'],
            'from'      => ['nullable', 'date'],
            'to'        => ['nullable', 'date'],
            'min_score' => ['nullable', 'numeric', 'between:1,5'],
            'max_score' => ['nullable', 'numeric', 'between:1,5'],
        ]);
    }
}
