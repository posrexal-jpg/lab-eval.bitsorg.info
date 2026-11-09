<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\FormTemplate;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicEvaluationController extends Controller
{
    public function show(Request $request, string $token)
    {
        $room = $this->room($token);
        $template = FormTemplate::active();

        if (!$template) {
            return response()->view('evaluate.closed', ['room' => $room, 'reason' => 'no-form'], 503);
        }

        if ($this->alreadySubmitted($request, $room)) {
            return view('evaluate.thanks', ['room' => $room, 'repeat' => true]);
        }

        return view('evaluate.show', compact('room', 'template'));
    }

    public function store(Request $request, string $token)
    {
        $room = $this->room($token);

        // Honeypot: real people never see this field. Pretend success so bots learn nothing.
        if ($request->filled('contact_url_hp')) {
            return redirect()->route('evaluate.thanks', $token);
        }

        if ($this->alreadySubmitted($request, $room)) {
            return redirect()->route('evaluate.thanks', $token);
        }

        // Time trap: the signed timestamp was issued when the form was first rendered.
        try {
            $issued = (int) Crypt::decryptString((string) $request->input('_ts'));
        } catch (\Throwable) {
            $issued = 0;
        }
        $age = time() - $issued;
        if ($issued === 0 || $age > 6 * 3600) {
            return back()->withInput()->withErrors(['form' => 'This form has expired. Your answers were kept; please submit again.']);
        }
        if ($age < config('evaluation.min_seconds_to_submit')) {
            return back()->withInput()->withErrors(['form' => 'That was quick! Please review your answers and submit again.']);
        }

        $template = FormTemplate::findOrFail((int) $request->input('_tpl'));
        $fields = $template->fields();

        $validator = Validator::make($request->all(), $this->rules($fields));
        $validator->setAttributeNames(collect($fields)->pluck('label', 'name')->all());
        $validator->validate();

        $answers = array_filter(
            Arr::only($validator->validated(), array_column($fields, 'name')),
            fn ($v) => $v !== null && $v !== '' && $v !== []
        );

        if (!$answers) {
            return back()->withInput()->withErrors(['form' => 'Please answer at least one item before submitting.']);
        }

        $scores = [];
        foreach ($fields as $f) {
            if ($f['rating'] && isset($answers[$f['name']]) && is_numeric($answers[$f['name']])) {
                $scores[] = (int) $answers[$f['name']];
            }
        }

        DB::transaction(function () use ($room, $template, $answers, $scores, $request) {
            Evaluation::create([
                'room_id'          => $room->id,
                'form_template_id' => $template->id,
                'answers'          => $answers,
                'average_score'    => $scores ? round(array_sum($scores) / count($scores), 2) : null,
                'rating_count'     => count($scores),
                'status'           => 'visible',
                'ip_hash'          => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                'user_agent'       => Str::limit((string) $request->userAgent(), 250, ''),
            ]);
        });

        $hours = config('evaluation.duplicate_cookie_hours');
        if ($hours > 0) {
            Cookie::queue($this->cookieName($room), '1', $hours * 60, '/', null, $request->isSecure(), true, false, 'lax');
        }

        return redirect()->route('evaluate.thanks', $token);
    }

    public function thanks(string $token)
    {
        return view('evaluate.thanks', ['room' => $this->room($token), 'repeat' => false]);
    }

    /* ------------------------------------------------------------------ */

    private function room(string $token): Room
    {
        return Room::where('token', $token)->where('is_active', true)->firstOrFail();
    }

    private function cookieName(Room $room): string
    {
        return 'evald_'.$room->id;
    }

    private function alreadySubmitted(Request $request, Room $room): bool
    {
        return config('evaluation.duplicate_cookie_hours') > 0 && $request->cookie($this->cookieName($room)) !== null;
    }

    private function rules(array $fields): array
    {
        $rules = [];

        foreach ($fields as $f) {
            $n = $f['name'];
            $presence = $f['required'] ? 'required' : 'nullable';

            switch ($f['type']) {
                case 'radio':
                case 'select':
                    $rules[$n] = [$presence, 'string', Rule::in($f['options'])];
                    break;
                case 'checkbox':
                    if ($f['multi']) {
                        $rules[$n] = [$presence, 'array'];
                        $rules["$n.*"] = ['string', Rule::in($f['options'])];
                    } else {
                        $rules[$n] = [$presence, 'string', Rule::in($f['options'])];
                    }
                    break;
                case 'textarea':
                    $rules[$n] = [$presence, 'string', 'max:3000'];
                    break;
                case 'date':
                    $rules[$n] = [$presence, 'date'];
                    break;
                case 'time':
                    $rules[$n] = [$presence, 'date_format:H:i'];
                    break;
                case 'number':
                    $rules[$n] = [$presence, 'numeric'];
                    break;
                case 'email':
                    $rules[$n] = [$presence, 'email', 'max:255'];
                    break;
                default:
                    $rules[$n] = [$presence, 'string', 'max:255'];
            }
        }

        return $rules;
    }
}
