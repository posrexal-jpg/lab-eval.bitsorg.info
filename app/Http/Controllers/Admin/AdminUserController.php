<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('throttle:admin-sensitive', only: ['store', 'update', 'destroy'])];
    }

    public function index()
    {
        return view('admin.admins.index', ['admins' => User::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.admins.form', ['admin' => new User(['role' => 'admin', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:190', 'unique:users,email'],
            'role'     => ['required', 'in:admin,super_admin'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);
        $data['is_active'] = true;

        $user = User::create($data);
        AuditLog::record('admin.created', $user->email);

        return redirect()->route('admin.admins.index')->with('status', 'Admin added.');
    }

    public function edit(User $admin)
    {
        return view('admin.admins.form', compact('admin'));
    }

    public function update(Request $request, User $admin)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($admin->id)],
            'role'     => ['required', 'in:admin,super_admin'],
            'password' => ['nullable', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($error = $this->guardLastSuper($admin, $data['role'], $data['is_active'], $request)) {
            return back()->withInput()->withErrors(['role' => $error]);
        }

        $admin->update($data);
        AuditLog::record('admin.updated', $admin->email);

        return redirect()->route('admin.admins.index')->with('status', 'Admin updated.');
    }

    public function destroy(Request $request, User $admin)
    {
        if ($error = $this->guardLastSuper($admin, 'deleted', false, $request)) {
            return back()->withErrors(['admin' => $error]);
        }

        AuditLog::record('admin.deleted', $admin->email);
        $admin->delete();

        return redirect()->route('admin.admins.index')->with('status', 'Admin removed.');
    }

    /** Prevent self-lockout and removing the last active super admin. */
    private function guardLastSuper(User $target, string $newRole, bool $newActive, Request $request): ?string
    {
        if ($target->id === $request->user()->id && (!$newActive || $newRole !== 'super_admin')) {
            return 'You cannot deactivate, demote or delete your own account.';
        }

        $otherSupers = User::where('role', 'super_admin')->where('is_active', true)->where('id', '!=', $target->id)->count();
        if ($target->isSuperAdmin() && $otherSupers === 0 && (!$newActive || $newRole !== 'super_admin')) {
            return 'At least one active super admin must remain.';
        }

        return null;
    }
}
