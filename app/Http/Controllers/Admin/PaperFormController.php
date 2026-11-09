<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Room;
use App\Services\PaperFormRenderer;
use Illuminate\Http\Request;

/** Prints the blank (or room pre-filled) ISO paper form. */
class PaperFormController extends Controller
{
    private const MAX_SHEETS = 200;

    public function index()
    {
        return view('admin.paper-form', ['rooms' => Room::where('is_active', true)->orderBy('name')->get()]);
    }

    public function print(Request $request, PaperFormRenderer $renderer)
    {
        $data = $request->validate([
            'room_id'   => ['nullable', 'regex:/^(all|\d+)$/'],
            'copies'    => ['required', 'integer', 'min:1', 'max:'.self::MAX_SHEETS],
            'fill_date' => ['nullable', 'boolean'],
        ]);

        $rooms = match (true) {
            empty($data['room_id'])    => [null],
            $data['room_id'] === 'all' => Room::where('is_active', true)->orderBy('name')->get()->all(),
            default                    => [Room::findOrFail((int) $data['room_id'])],
        };

        $total = count($rooms) * $data['copies'];
        if ($total > self::MAX_SHEETS) {
            return back()->withErrors(['copies' => 'That would print more than '.self::MAX_SHEETS.' sheets at once. Lower the number of copies.']);
        }

        $sheets = '';
        foreach ($rooms as $room) {
            $answers = [];
            if ($room) {
                $answers['room_no'] = $room->name;
                if (preg_match('/^Computer Laboratory [123]$/i', trim($room->name))) {
                    $answers['laboratory'] = 'Computer Laboratory '.substr(trim($room->name), -1);
                }
            }
            if ($request->boolean('fill_date')) {
                $answers['date'] = now()->toDateString();
            }
            $sheets .= str_repeat($renderer->sheet($answers), $data['copies']);
        }

        AuditLog::record('paper_form.printed', $total.' blank sheet(s)');

        return $renderer->page($sheets, "<b>$total sheet".($total === 1 ? '' : 's').'</b> ready.', route('admin.paper-form'));
    }
}
