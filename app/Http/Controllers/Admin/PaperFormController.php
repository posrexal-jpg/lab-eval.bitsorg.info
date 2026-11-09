<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Room;
use Illuminate\Http\Request;

/**
 * Prints the paper version of the form (resources/print/paper-form.html) on A4.
 * The file is used exactly as designed; we only fill the room / date cells when asked.
 */
class PaperFormController extends Controller
{
    private const MAX_SHEETS = 200;

    public function index()
    {
        return view('admin.paper-form', ['rooms' => Room::where('is_active', true)->orderBy('name')->get()]);
    }

    public function print(Request $request)
    {
        $data = $request->validate([
            'room_id'   => ['nullable', 'regex:/^(all|\d+)$/'],
            'copies'    => ['required', 'integer', 'min:1', 'max:'.self::MAX_SHEETS],
            'fill_date' => ['nullable', 'boolean'],
        ]);

        $rooms = match (true) {
            empty($data['room_id'])     => [null],                                  // blank form
            $data['room_id'] === 'all'  => Room::where('is_active', true)->orderBy('name')->get()->all(),
            default                     => [Room::findOrFail((int) $data['room_id'])],
        };

        if (count($rooms) * $data['copies'] > self::MAX_SHEETS) {
            return back()->withErrors(['copies' => 'That would print more than '.self::MAX_SHEETS.' sheets at once. Lower the number of copies.']);
        }

        $source = file_get_contents(resource_path('print/paper-form.html'));
        $headEnd = strpos($source, '<body>');
        $bodyEnd = strrpos($source, '</body>');
        $head = substr($source, 0, $headEnd);
        $page = substr($source, $headEnd + 6, $bodyEnd - $headEnd - 6);

        $date = $request->boolean('fill_date') ? now()->format('F j, Y') : null;

        $sheets = '';
        foreach ($rooms as $room) {
            $filled = $this->fill($page, $room, $date);
            for ($i = 0; $i < $data['copies']; $i++) {
                $sheets .= '<div class="sheet">'.$filled.'</div>';
            }
        }

        $total = count($rooms) * $data['copies'];
        AuditLog::record('paper_form.printed', $total.' sheet(s)');

        $extraCss = '<style>'
            .'.toolbar{max-width:8.27in;margin:0 auto 12px;padding:10px 14px;background:#14284b;color:#fff;font:14px/1.4 "Segoe UI",Arial,sans-serif;display:flex;gap:12px;justify-content:space-between;align-items:center;flex-wrap:wrap}'
            .'.toolbar a,.toolbar button{background:#fff;color:#14284b;border:0;border-radius:5px;padding:7px 14px;font:inherit;cursor:pointer;text-decoration:none}'
            .'.sheet{margin-bottom:16px}'
            .'@media print{.toolbar{display:none!important}.sheet{margin:0;break-after:page;page-break-after:always}.sheet:last-child{break-after:auto;page-break-after:auto}'
            .'*{-webkit-print-color-adjust:exact;print-color-adjust:exact}}'
            .'</style>';

        $toolbar = '<div class="toolbar"><span><b>'.$total.' sheet'.($total === 1 ? '' : 's').'</b> ready. In the print dialog choose <b>A4</b>, margins <b>None</b>, and tick <b>Background graphics</b>.</span>'
            .'<span><button type="button" data-print>Print</button> <a href="'.e(route('admin.paper-form')).'">Back</a></span></div>'
            .'<script src="'.e(asset('js/app.js')).'" defer></script>';

        $html = str_replace('</head>', $extraCss.'</head>', $head)
            .'<body>'.$toolbar.$sheets.'</body></html>';
        $html = str_replace('__ASSET__', e(asset('print')), $html);

        // This page needs Google Fonts (Poppins) for the letterhead; everything else stays locked down.
        return response($html, 200, [
            'Content-Type'            => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self'; base-uri 'none'; frame-ancestors 'none'",
        ]);
    }

    /** Fills the "Laboratory / Room No." cell, ticks "Computer Laboratory 1/2/3" when the name matches, and optionally the date. */
    private function fill(string $page, ?Room $room, ?string $date): string
    {
        if ($room) {
            $page = str_replace(
                'Laboratory / Room No.</p></td><td></td>',
                'Laboratory / Room No.</p></td><td><p class="s85">'.e($room->name).'</p></td>',
                $page
            );

            if (preg_match('/^Computer Laboratory ([123])$/i', trim($room->name), $m)) {
                $page = str_replace(
                    '&#9744; Computer Laboratory '.$m[1],
                    '&#9745; Computer Laboratory '.$m[1],
                    $page
                );
            }
        }

        if ($date) {
            $page = str_replace(
                'Date</p></td><td></td></tr>',
                'Date</p></td><td><p class="s85">'.e($date).'</p></td></tr>',
                $page
            );
        }

        return $page;
    }
}
