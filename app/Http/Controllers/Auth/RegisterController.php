<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegisterRequest;
use App\Models\User;
use App\Services\PasswordHasher;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(StoreRegisterRequest $request, PasswordHasher $passwordHasher): RedirectResponse
    {
        $validated = $request->validated();

        $hashedPassword = $passwordHasher->hash(
            $validated['password']
        );

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $hashedPassword,
        ]);

        return redirect()
            ->route('login')
            ->with('success', __('message.auth.registered'));
    }
}
