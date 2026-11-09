<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'subject', 'ip'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?string $subject = null): void
    {
        static::create([
            'user_id' => Auth::id(),
            'action'  => $action,
            'subject' => $subject ? mb_substr($subject, 0, 250) : null,
            'ip'      => request()->ip(),
        ]);
    }
}
