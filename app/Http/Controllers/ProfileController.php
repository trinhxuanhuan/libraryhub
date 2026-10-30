<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ConfirmPasswordChangeRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Services\ManualAuthenticator;
use App\Services\PasswordChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ManualAuthenticator $authenticator,
        private readonly PasswordChangeService $passwordChangeService
    ) {}

    public function show(Request $request): View
    {
        return view('profile.show', [
            'user' => $this->currentUser($request),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $this->currentUser($request);

        $user->update($request->validated());

        return redirect()
            ->route('profile.show')
            ->with('success', __('message.profile.updated'));
    }

    public function requestPasswordChange(
        ChangePasswordRequest $request
    ): RedirectResponse {
        $user = $this->currentUser($request);
        $data = $request->validated();

        $this->passwordChangeService->start(
            $user,
            $data['current_password'],
            $data['password']
        );

        return redirect()
            ->route('profile.password.verify')
            ->with('success', 'Mã xác thực đã được gửi đến email của bạn.');
    }

    public function verifyPasswordChange(Request $request): View
    {
        return view('profile.verify-password', [
            'email' => $this->currentUser($request)->email,
        ]);
    }

    public function confirmPasswordChange(
        ConfirmPasswordChangeRequest $request
    ): RedirectResponse {
        $user = $this->currentUser($request);

        $this->passwordChangeService->confirm(
            $user,
            $request->validated('otp')
        );

        $request->session()->regenerate();

        return redirect()
            ->route('profile.show')
            ->with('success', __('message.profile.password_changed'));
    }

    public function resendPasswordChange(Request $request): RedirectResponse
    {
        $this->passwordChangeService->resend(
            $this->currentUser($request)
        );

        return redirect()
            ->route('profile.password.verify')
            ->with('success', __('message.profile.otp_resent'));
    }

    private function currentUser(Request $request): User
    {
        $user = $this->authenticator->user($request);

        if ($user === null) {
            abort(401);
        }

        return $user;
    }
}
