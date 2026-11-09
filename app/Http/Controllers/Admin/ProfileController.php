<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', 'different:current_password', Password::min(10)->mixedCase()->numbers()],
        ]);

        $request->user()->update(['password' => $data['password']]);
        Auth::logoutOtherDevices($data['password']);
        AuditLog::record('password.changed');

        return back()->with('status', 'Password changed. Other devices were signed out.');
    }
}
