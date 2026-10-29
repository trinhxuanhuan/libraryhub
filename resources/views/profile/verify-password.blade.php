@extends('layouts.app')

@section('title', 'Xác thực đổi mật khẩu — LibraryHub')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Xác thực đổi mật khẩu</h1>

                <p class="text-muted">
                    Mã xác thực đã được gửi đến
                    <strong>{{ $email }}</strong>.
                    Mã có hiệu lực trong 10 phút.
                </p>

                <form
                    method="POST"
                    action="{{ route('profile.password.confirm') }}"
                >
                    @csrf

                    <div class="mb-3">
                        <label for="otp" class="form-label">
                            Mã xác thực gồm 6 chữ số
                        </label>

                        <input
                            type="text"
                            id="otp"
                            name="otp"
                            class="form-control @error('otp') is-invalid @enderror"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            autocomplete="one-time-code"
                            required
                            autofocus
                        >

                        @error('otp')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Xác nhận đổi mật khẩu
                    </button>
                </form>

                <form
                    method="POST"
                    action="{{ route('profile.password.resend') }}"
                    class="mt-3"
                >
                    @csrf

                    <button type="submit" class="btn btn-link w-100">
                        Gửi lại mã xác thực
                    </button>
                </form>

                <a
                    href="{{ route('profile.show') }}"
                    class="btn btn-outline-secondary w-100 mt-2"
                >
                    Quay lại hồ sơ
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
