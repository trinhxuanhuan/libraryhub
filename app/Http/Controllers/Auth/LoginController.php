<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\ManualAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(
        LoginRequest $request,
        ManualAuthenticator $authenticator
    ): RedirectResponse {
        // Lấy dữ liệu đã validate
        $validated = $request->validated();

        // Tự thực hiện xác thực và tạo Session
        $isAuthenticated = $authenticator->login(
            $request,
            $validated['email'],
            $validated['password']
        );

        // Xử lý đăng nhập thất bại
        if (! $isAuthenticated) {
            return back()
                ->withErrors([
                    'email' => __('message.auth.failed'),
                ])
                ->onlyInput('email');
        }

        // Đăng nhập thành công
        return redirect()
            ->intended(route('home'))
            ->with('success', __('message.auth.logged_in'));
    }

    public function destroy(
        Request $request,
        ManualAuthenticator $authenticator
    ): RedirectResponse {
        // Hủy phiên đăng nhập
        $authenticator->logout($request);

        return redirect()
            ->route('home')
            ->with('success', __('message.auth.logged_out'));
    }
}
