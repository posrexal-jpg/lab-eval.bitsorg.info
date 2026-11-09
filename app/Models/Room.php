<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'building', 'capacity', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Room $room) {
            $room->token = $room->token ?: Str::random(32);
        });
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function regenerateToken(): void
    {
        $this->forceFill(['token' => Str::random(32)])->save();
    }

    public function evaluationUrl(): string
    {
        return route('evaluate.show', $this->token);
    }
}
