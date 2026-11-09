<?php

namespace App\Services;

use App\Models\Evaluation;
use App\Models\FormTemplate;

class ReportService
{
    /** Hidden responses are excluded unless a status filter is given explicitly. */
    public function query(array $filters)
    {
        return Evaluation::query()
            ->filter($filters)
            ->when(empty($filters['status']), fn ($q) => $q->counted());
    }

    public function build(array $filters): array
    {
        // Field metadata from every template version; newer versions win.
        $meta = [];
        foreach (FormTemplate::orderBy('id')->get() as $t) {
            foreach ($t->fields() as $f) {
                $meta[$f['name']] = $f;
            }
        }

        $total = 0;
        $ratedCount = 0;
        $sum = 0.0;
        $rooms = [];
        $crit = [];
        $overallDist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $comments = [];
        $respondents = [];

        $this->query($filters)->with('room')->chunkById(500, function ($rows) use (
            &$total, &$ratedCount, &$sum, &$rooms, &$crit, &$overallDist, &$comments, &$respondents, $meta
        ) {
            foreach ($rows as $ev) {
                $total++;
                $rid = $ev->room_id;
                $rooms[$rid] ??= ['name' => $ev->room->name, 'count' => 0, 'rated' => 0, 'sum' => 0.0];
                $rooms[$rid]['count']++;

                if ($ev->average_score !== null) {
                    $ratedCount++;
                    $sum += $ev->average_score;
                    $rooms[$rid]['rated']++;
                    $rooms[$rid]['sum'] += $ev->average_score;
                }

                foreach ($ev->answers as $name => $val) {
                    $m = $meta[$name] ?? null;
                    if (!$m) {
                        continue;
                    }

                    if ($m['rating'] && is_numeric($val) && $val >= 1 && $val <= 5) {
                        $v = (int) $val;
                        $crit[$name] ??= ['n' => 0, 'sum' => 0, 'dist' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];
                        $crit[$name]['n']++;
                        $crit[$name]['sum'] += $v;
                        $crit[$name]['dist'][$v]++;
                        $overallDist[$v]++;
                    } elseif ($m['type'] === 'textarea' && is_string($val) && trim($val) !== '') {
                        $comments[] = [
                            'date'  => $ev->created_at->format('M j, Y'),
                            'room'  => $ev->room->name,
                            'label' => $m['label'],
                            'text'  => $val,
                        ];
                    } elseif ($name === 'respondent_type' && is_string($val)) {
                        $respondents[$val] = ($respondents[$val] ?? 0) + 1;
                    }
                }
            }
        });

        // Rooms
        $roomRows = collect($rooms)->map(fn ($r) => $r + [
            'avg' => $r['rated'] ? round($r['sum'] / $r['rated'], 2) : null,
        ])->sortBy('name')->values()->all();

        // Criteria, keeping the order they appear in the form
        $criteria = [];
        foreach ($meta as $name => $m) {
            if ($m['rating'] && isset($crit[$name])) {
                $c = $crit[$name];
                $criteria[] = [
                    'section' => $m['section'] ?? 'Ratings',
                    'label'   => $m['label'],
                    'n'       => $c['n'],
                    'avg'     => round($c['sum'] / $c['n'], 2),
                    'dist'    => $c['dist'],
                ];
            }
        }

        $lowest = collect($criteria)->sortBy('avg')->take(3)->values()->all();

        arsort($respondents);

        return [
            'total'       => $total,
            'avg'         => $ratedCount ? round($sum / $ratedCount, 2) : null,
            'rooms'       => $roomRows,
            'criteria'    => collect($criteria)->groupBy('section')->all(),
            'lowest'      => $lowest,
            'dist'        => $overallDist,
            'comments'    => $comments,
            'respondents' => $respondents,
        ];
    }
}
