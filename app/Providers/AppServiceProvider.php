<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $limits = fn (string $key) => (int) config("evaluation.limits.$key");

        // Admin login: per email+IP, and a looser per-IP cap against credential stuffing.
        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute($limits('login_per_minute'))->by(Str::lower((string) $r->input('email')).'|'.$r->ip()),
            Limit::perMinute($limits('login_per_minute') * 4)->by('ip:'.$r->ip()),
        ]);

        // Public evaluation page views (QR scans).
        RateLimiter::for('evaluation-view', fn (Request $r) => Limit::perMinute($limits('view_per_minute'))->by($r->ip()));

        // Public submissions: per room per IP, and overall per IP.
        RateLimiter::for('evaluation-submit', fn (Request $r) => [
            Limit::perHour($limits('submit_per_room_hour'))
                ->by('room:'.$r->route('token').'|'.$r->ip())
                ->response(fn () => response()->view('evaluate.throttled', [], 429)),
            Limit::perHour($limits('submit_per_ip_hour'))
                ->by('ip:'.$r->ip())
                ->response(fn () => response()->view('evaluate.throttled', [], 429)),
        ]);

        // Template uploads.
        RateLimiter::for('admin-upload', fn (Request $r) => Limit::perMinute($limits('admin_upload_per_minute'))->by('u:'.$r->user()?->id));

        // Creating admins, resetting passwords, changing own password, regenerating QR tokens.
        RateLimiter::for('admin-sensitive', fn (Request $r) => Limit::perMinute($limits('admin_sensitive_per_min'))->by('u:'.$r->user()?->id));
    }
}
