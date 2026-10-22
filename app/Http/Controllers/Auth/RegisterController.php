<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(StoreRegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $hashedPassword = password_hash(
            $validated['password'],
            PASSWORD_BCRYPT,
            ['cost' => (int) config('hashing.bcrypt.rounds', 12)]
        );

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $hashedPassword,
        ]);

        return redirect()
            ->route('login')
            ->with('success', __('message.auth.registered'));
    }
}
