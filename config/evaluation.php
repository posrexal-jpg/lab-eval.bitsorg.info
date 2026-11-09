<?php

return [
    'institution' => [
        'name'   => env('INSTITUTION_NAME', 'Southern Leyte State University'),
        'campus' => env('INSTITUTION_CAMPUS', 'Bontoc Campus, San Ramon, Bontoc, Southern Leyte'),
    ],

    // All limits are adjustable from .env
    'limits' => [
        'login_per_minute'        => (int) env('LIMIT_LOGIN_PER_MINUTE', 5),
        'submit_per_room_hour'    => (int) env('LIMIT_SUBMIT_PER_ROOM_HOUR', 3),
        'submit_per_ip_hour'      => (int) env('LIMIT_SUBMIT_PER_IP_HOUR', 15),
        'view_per_minute'         => (int) env('LIMIT_VIEW_PER_MINUTE', 60),
        'admin_upload_per_minute' => (int) env('LIMIT_ADMIN_UPLOAD_PER_MINUTE', 10),
        'admin_sensitive_per_min' => (int) env('LIMIT_ADMIN_SENSITIVE_PER_MINUTE', 10),
    ],

    // A form submitted faster than this is treated as a bot.
    'min_seconds_to_submit' => (int) env('EVAL_MIN_SECONDS', 10),

    // After submitting, the same device cannot submit for the same room for N hours. 0 = off.
    'duplicate_cookie_hours' => (int) env('EVAL_DUPLICATE_HOURS', 6),

    // Rooms whose average falls under this are highlighted as "needs attention".
    'low_score_threshold' => (float) env('EVAL_LOW_SCORE', 3.0),

    'template_max_kb' => 1024,
];
