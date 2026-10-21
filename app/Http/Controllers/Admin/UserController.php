<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use App\Http\Requests\UpdateUserRequest;
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
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $role = UserRole::from($validated['role']);
        $isActive = (bool) $validated['is_active'];

        if ($request->user()->is($user)) {
            if ($role !== UserRole::Admin) {
                return back()
                    ->withErrors(['role' => 'Bạn không thể tự hạ quyền Admin.'])
                    ->withInput();
            }

            if (! $isActive) {
                return back()
                    ->withErrors(['is_active' => 'Bạn không thể tự khóa tài khoản.'])
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
