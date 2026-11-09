<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $fillable = [
        'room_id', 'form_template_id', 'answers', 'average_score', 'rating_count',
        'status', 'ip_hash', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['answers' => 'array', 'average_score' => 'float'];
    }

    public function room()
    {
        return $this->belongsTo(Room::class)->withTrashed();
    }

    public function template()
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function scopeCounted(Builder $q): Builder
    {
        return $q->where('status', '!=', 'hidden');
    }

    /** Shared filter used by the response list, reports and CSV export. */
    public function scopeFilter(Builder $q, array $f): Builder
    {
        return $q
            ->when(!empty($f['room_id']), fn ($q) => $q->whereIn('room_id', (array) $f['room_id']))
            ->when(!empty($f['template_id']), fn ($q) => $q->where('form_template_id', $f['template_id']))
            ->when(!empty($f['status']), fn ($q) => $q->where('status', $f['status']))
            ->when(!empty($f['from']), fn ($q) => $q->where('created_at', '>=', Carbon::parse($f['from'])->startOfDay()))
            ->when(!empty($f['to']), fn ($q) => $q->where('created_at', '<=', Carbon::parse($f['to'])->endOfDay()))
            ->when(isset($f['min_score']) && $f['min_score'] !== '', fn ($q) => $q->where('average_score', '>=', $f['min_score']))
            ->when(isset($f['max_score']) && $f['max_score'] !== '', fn ($q) => $q->where('average_score', '<=', $f['max_score']));
    }

    /** 5-point scale interpretation. */
    public static function describe(?float $avg): string
    {
        return match (true) {
            $avg === null => 'No rating',
            $avg >= 4.5   => 'Very Satisfied',
            $avg >= 3.5   => 'Satisfied',
            $avg >= 2.5   => 'Neutral',
            $avg >= 1.5   => 'Dissatisfied',
            default       => 'Very Dissatisfied',
        };
    }

    public static function tone(?float $avg): string
    {
        if ($avg === null) return 'muted';
        if ($avg >= 3.5) return 'good';
        if ($avg >= config('evaluation.low_score_threshold')) return 'warn';
        return 'bad';
    }
}
