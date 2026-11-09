# Laboratory Evaluation (Laravel 11 / 12)

QR-based evaluation of computer laboratories, built around the **SLSU Computer Laboratory User Satisfaction Form**
(already loaded as the first, live template). This folder is an *overlay*: copy it onto a fresh Laravel project.

## Install

```bash
composer create-project laravel/laravel lab-eval      # or: laravel new lab-eval  (no starter kit)
cd lab-eval
composer require bacon/bacon-qr-code                   # QR codes (SVG, no PHP image extension needed)

# copy everything from this overlay into the project, overwriting when asked:
#   app/  bootstrap/app.php  config/evaluation.php  database/  public/  resources/views/  routes/web.php
```

Add to `.env`:

```dotenv
APP_URL=https://your-lab-server.example      # QR codes encode this address. It MUST be what phones can reach.
APP_TIMEZONE=Asia/Manila                     # also set 'timezone' => env('APP_TIMEZONE', 'UTC') in config/app.php
SESSION_SECURE_COOKIE=true                   # when served over HTTPS

SEED_ADMIN_EMAIL=you@school.edu.ph
SEED_ADMIN_PASSWORD=ChangeMe12345            # omit it and a random one is printed by the seeder

# Optional, all have defaults (see config/evaluation.php)
LIMIT_LOGIN_PER_MINUTE=5
LIMIT_SUBMIT_PER_ROOM_HOUR=3
LIMIT_SUBMIT_PER_IP_HOUR=15
EVAL_MIN_SECONDS=10
EVAL_DUPLICATE_HOURS=6
EVAL_LOW_SCORE=3.0
```

```bash
php artisan migrate --seed
php artisan serve        # then open /login
```

Locked out? `php artisan lab:create-admin someone@school.edu.ph --super`

## What you get

| Area | Features |
|---|---|
| **Admin accounts** | Super admin / admin roles, add / edit / disable / delete admins, safeguards against self-lockout and removing the last super admin, password change that signs out other devices, audit log |
| **Rooms & QR** | Add rooms, unique unguessable QR per room, view / download SVG, print one, selected or all QR sheets, regenerate a QR if a printed one is abused |
| **Form templates** | Upload any `.html` form, it is sanitized and its fields detected automatically, preview, versioning, one live template at a time, old responses keep their version |
| **Evaluation page** | Opened by scanning the QR, mobile-friendly, validates every answer against the template, room and today’s date pre-filled, answers restored after an error |
| **Monitor** | Per-room today / 7-day counts and averages, "needs attention" flag under the threshold, auto-refresh, printable monitoring sheet with signature lines |
| **Responses** | Filter by room / date / score / status, view and print a single response, flag / hide (spam) / delete |
| **Reports** | Overall average with 5-point interpretation, rating distribution, by room, by respondent type, by criterion (grouped by form section), three lowest criteria, all written comments, printable with signature lines, CSV export (Excel-safe, formula-injection protected) |

## Rate limiting and abuse protection

| Target | Limit (default) |
|---|---|
| Admin login | 5/min per email+IP, 20/min per IP |
| QR page views | 60/min per IP |
| Evaluation submits | 3/hour per room per IP, 15/hour per IP (friendly 429 page) |
| Template upload | 10/min per admin |
| New admin / password change / QR regenerate | 10/min per admin |

Also: hidden honeypot field, minimum fill time (signed timestamp), one submission per device per room for N hours (cookie),
CSRF everywhere, hashed IPs (the raw IP is never stored), strict CSP (no inline scripts), security headers,
upload sanitizer (strips scripts, iframes, buttons, external images/CSS, event handlers, `url()`).

Rate limits use the cache, so use `file`, `database` or `redis` (not `array`). Behind a proxy or Cloudflare, enable
`$middleware->trustProxies(at: '*')` in `bootstrap/app.php`, otherwise every visitor looks like one IP.

## Writing your own template

- Every `<input>`, `<select>`, `<textarea>` needs a `name`. Do not add `<form>` or a submit button.
- Radio groups with values `5,4,3,2,1` (and optionally `NA`) are **rated questions** and feed all averages.
- Question text: `data-label="..."` on the row, otherwise the first cell of the row.
- `<tr class="section"><td>1. FACILITIES</td></tr>` groups the questions that follow it.
- Add `required` to make an item mandatory.
- Field names `respondent_type` and `room_no` get special treatment (respondent breakdown, prefill with the room name).
- Download the bundled sample from **Form templates → Download sample .html**.

## Notes

- Respondent names are optional in the form and stored only if typed. IPs are stored hashed.
- Deleting a room is a soft delete, so its past responses still appear in reports.
- The wall monitor page (`/admin/monitor`) works well on a spare screen near the lab: it refreshes by itself.
- Not run yet: this overlay was written without a PHP runtime. Run `php artisan migrate --seed`, log in, upload the sample,
  scan a QR and submit once as a smoke test. Please send me any error you see.
