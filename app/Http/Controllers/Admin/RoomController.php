<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Services\QrService;

class RoomController extends Controller
{
    public function __construct(private QrService $qr)
    {
    }

    public function index()
    {
        $rooms = Room::query()
            ->withCount(['evaluations as responses' => fn ($q) => $q->counted()])
            ->withAvg(['evaluations as avg' => fn ($q) => $q->counted()], 'average_score')
            ->orderBy('name')->get();

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('admin.rooms.form', ['room' => new Room(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $room = Room::create($this->validated($request));
        AuditLog::record('room.created', $room->name);

        return redirect()->route('admin.rooms.qr', $room)->with('status', 'Room created. Print its QR code below.');
    }

    public function edit(Room $room)
    {
        return view('admin.rooms.form', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        $room->update($this->validated($request));
        AuditLog::record('room.updated', $room->name);

        return redirect()->route('admin.rooms.index')->with('status', 'Room updated.');
    }

    public function destroy(Room $room)
    {
        // Soft delete: past responses keep their room name in reports.
        $room->delete();
        AuditLog::record('room.deleted', $room->name);

        return redirect()->route('admin.rooms.index')->with('status', 'Room removed. Its past responses are kept.');
    }

    public function qr(Room $room)
    {
        return view('admin.rooms.qr', ['room' => $room, 'svg' => $this->qr->svg($room->evaluationUrl(), 320)]);
    }

    public function qrDownload(Room $room)
    {
        return response($this->qr->svg($room->evaluationUrl(), 1024, false), 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="qr-'.str($room->name)->slug().'.svg"',
        ]);
    }

    public function qrPrint(Request $request)
    {
        $ids = array_filter((array) $request->query('ids'), 'is_numeric');
        $rooms = Room::where('is_active', true)
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('name')->get()
            ->map(fn ($r) => ['room' => $r, 'svg' => $this->qr->svg($r->evaluationUrl(), 280)]);

        return view('admin.rooms.qr-print', compact('rooms'));
    }

    /** Invalidates every printed QR for this room. */
    public function regenerate(Room $room)
    {
        $room->regenerateToken();
        AuditLog::record('room.qr_regenerated', $room->name);

        return back()->with('status', 'New QR code generated. Reprint it: the old one no longer works.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'building' => ['nullable', 'string', 'max:120'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
