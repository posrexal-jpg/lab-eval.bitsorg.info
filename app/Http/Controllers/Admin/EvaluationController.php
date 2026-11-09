<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Evaluation;
use App\Models\Room;
use App\Services\PaperFormRenderer;
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

    /** One response printed on the ISO paper form. */
    public function printForm(Evaluation $evaluation, PaperFormRenderer $renderer)
    {
        $evaluation->load('room');

        if (!$renderer->hasMatchingAnswers($evaluation->answers)) {
            return back()->withErrors(['form' => 'This response was submitted on a different form layout, so it cannot be placed on the paper form.']);
        }

        AuditLog::record('response.printed_on_form', '#'.$evaluation->id);

        return $renderer->page(
            $renderer->sheet($this->paperAnswers($evaluation)),
            'Response <b>#'.$evaluation->id.'</b> · '.e($evaluation->room->name).' · '.$evaluation->created_at->format('M j, Y g:i A').'.',
            route('admin.evaluations.show', $evaluation)
        );
    }

    /** All responses matching the current filters, one sheet each (max 100). */
    public function printMany(Request $request, PaperFormRenderer $renderer)
    {
        $filters = $this->filters($request);
        $rows = Evaluation::with('room')->filter($filters)->counted()->orderBy('id')->limit(101)->get();

        if ($rows->isEmpty()) {
            return back()->withErrors(['form' => 'No responses match the filters.']);
        }

        $capped = $rows->count() > 100;
        $rows = $rows->take(100);
        $sheets = $rows->filter(fn ($e) => $renderer->hasMatchingAnswers($e->answers))
            ->map(fn ($e) => $renderer->sheet($this->paperAnswers($e)))->implode('');

        if ($sheets === '') {
            return back()->withErrors(['form' => 'None of these responses use the paper form layout.']);
        }

        AuditLog::record('response.printed_on_form', $rows->count().' response(s)');

        return $renderer->page(
            $sheets,
            '<b>'.$rows->count().' sheet'.($rows->count() === 1 ? '' : 's').'</b> (oldest first)'.($capped ? ', limited to 100. Narrow the filters to print the rest' : '').'.',
            route('admin.evaluations.index', $filters)
        );
    }

    /** Answers plus sensible fallbacks for cells the respondent left empty. */
    private function paperAnswers(Evaluation $evaluation): array
    {
        $answers = $evaluation->answers;
        $answers += ['room_no' => $evaluation->room->name, 'date' => $evaluation->created_at->toDateString()];

        return $answers;
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
