<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\ManualAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderBy('id')
            ->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user, ManualAuthenticator $authenticator): RedirectResponse
    {
        $validated = $request->validated();

        $role = UserRole::from((int) $validated['role']);
        $isActive = (bool) $validated['is_active'];

        // Lấy người đang đăng nhập từ Session thủ công
        $currentUser = $authenticator->user($request);

        if ($currentUser === null) {
            return redirect()->route('login');
        }

        if ($currentUser->is($user)) {
            if ($role !== UserRole::Admin) {
                return back()
                    ->withErrors([
                        'role' => __('message.users.cannot_self_demote'),
                    ])
                    ->withInput();
            }

            if (! $isActive) {
                return back()
                    ->withErrors([
                        'is_active' => __('message.users.cannot_self_deactivate'),
                    ])
                    ->withInput();
            }
        }

        $user->role = $role;
        $user->is_active = $isActive;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', __('message.users.updated'));
    }
}
