<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Response;

/**
 * Prints answers onto the ISO paper form (resources/print/paper-form.html).
 *
 * The HTML carries two kinds of markers:
 *   <span class="cb" data-o="field=value">&#9744;</span>   a tick box, ticked when answers[field] == value
 *   data-f="field"                                          a blank to fill with answers[field]
 * Field names are the ones used by the bundled online template (q1..q11, respondent_type, room_no, ...).
 */
class PaperFormRenderer
{
    /** Keys the paper form understands. */
    public const KEYS = [
        'name', 'date', 'respondent_type', 'respondent_type_other', 'course_section', 'laboratory',
        'laboratory_other', 'room_no', 'purpose', 'purpose_other', 'comment_useful', 'comment_improve',
        'q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'q8', 'q9', 'q10', 'q11',
    ];

    public function hasMatchingAnswers(array $answers): bool
    {
        return count(array_intersect(array_keys($answers), self::KEYS)) > 0;
    }

    /** One filled A4 sheet. Pass [] for a blank form. */
    public function sheet(array $answers): string
    {
        [, $page] = $this->source();

        // Tick boxes
        $page = preg_replace_callback(
            '#<span class="cb" data-o="([a-z_0-9]+)=([^"]*)">&\#9744;</span>#',
            function ($m) use ($answers) {
                $given = $answers[$m[1]] ?? null;
                $want = htmlspecialchars_decode($m[2]);
                $hit = is_array($given) ? in_array($want, $given, true) : (is_scalar($given) && (string) $given === $want);

                return $hit ? '<span class="cb on">&#9746;</span>' : '<span class="cb">&#9744;</span>';
            },
            $page
        );

        // Blanks
        $page = preg_replace_callback(
            '#<(td|p|span)([^>]*?) data-f="([a-z_0-9]+)"([^>]*)>(.*?)</\1>#s',
            function ($m) use ($answers) {
                [$all, $tag, $before, $key, $after, $default] = $m;
                $value = $answers[$key] ?? null;
                if ($key === 'date' && $value) {
                    try { $value = Carbon::parse($value)->format('F j, Y'); } catch (\Throwable) {}
                }
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $value = is_scalar($value) ? trim((string) $value) : '';
                if ($value === '') {
                    return "<$tag$before$after>$default</$tag>"; // keep the printed blank line
                }
                $long = mb_strlen($value) > 350 ? ' long' : '';
                $text = nl2br(e($value), false);

                return "<$tag$before$after><span class=\"ans$long\">$text</span></$tag>";
            },
            $page
        );

        return '<div class="sheet">'.$page.'</div>';
    }

    /** Wraps sheets into a printable page with a toolbar (hidden when printing). */
    public function page(string $sheets, string $message, string $backUrl): Response
    {
        [$head] = $this->source();

        $css = '<style>'
            .'.toolbar{max-width:8.27in;margin:0 auto 12px;padding:10px 14px;background:#14284b;color:#fff;font:14px/1.4 "Segoe UI",Arial,sans-serif;display:flex;gap:12px;justify-content:space-between;align-items:center;flex-wrap:wrap}'
            .'.toolbar a,.toolbar button{background:#fff;color:#14284b;border:0;border-radius:5px;padding:7px 14px;font:inherit;cursor:pointer;text-decoration:none}'
            .'.sheet{margin-bottom:16px}'
            .'.cb.on{font-weight:bold}'
            .'.ans{font-size:9pt;line-height:1.2}.ans.long{font-size:7.5pt}'
            .'p.e .ans{display:block;padding:1pt 0}'
            .'@media print{.toolbar{display:none!important}.sheet{margin:0;break-after:page;page-break-after:always}.sheet:last-child{break-after:auto;page-break-after:auto}'
            .'*{-webkit-print-color-adjust:exact;print-color-adjust:exact}}'
            .'</style>';

        $toolbar = '<div class="toolbar"><span>'.$message.' In the print dialog choose <b>A4</b>, margins <b>None</b>, and tick <b>Background graphics</b>.</span>'
            .'<span><button type="button" data-print>Print</button> <a href="'.e($backUrl).'">Back</a></span></div>'
            .'<script src="'.e(asset('js/app.js')).'" defer></script>';

        $html = str_replace('</head>', $css.'</head>', $head).'<body>'.$toolbar.$sheets.'</body></html>';
        $html = str_replace('__ASSET__', e(asset('print')), $html);

        // Poppins (letterhead) comes from Google Fonts; everything else stays locked down.
        return response($html, 200, [
            'Content-Type'            => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self'; base-uri 'none'; frame-ancestors 'none'",
        ]);
    }

    /** @return array{0: string, 1: string} head (up to <body>) and the page markup */
    private function source(): array
    {
        $source = file_get_contents(resource_path('print/paper-form.html'));
        $start = strpos($source, '<body>');
        $end = strrpos($source, '</body>');

        return [substr($source, 0, $start), substr($source, $start + 6, $end - $start - 6)];
    }
}
