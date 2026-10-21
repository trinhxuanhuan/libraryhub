@extends('layouts.app')

@section('title', 'Đăng ký - LibraryHub')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4">Đăng ký tài khoản</h2>

            <form action="{{ route('register.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Họ tên</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                    >

                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                    >

                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>

                    <div class="input-group">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                        >

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-password-toggle="password"
                            aria-label="Hiện mật khẩu"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2"
                                aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                                <path data-eye-slash d="M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>

                    @error('password')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">

                    <label for="password_confirmation" class="form-label">
                        Xác nhận mật khẩu
                    </label>

                    <div class="input-group">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control @error('password_confirmation') is-invalid @enderror"
                        >

                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-password-toggle="password_confirmation"
                            aria-label="Hiện mật khẩu"
                            aria-pressed="false"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2"
                                aria-hidden="true">
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                                <path data-eye-slash d="M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>

                    @error('password_confirmation')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    Đăng ký
                </button>
                <a href="{{ route('login') }}" class="ms-3">
                    Đã có tài khoản? Đăng nhập
                </a>
            </form>
        </div>
    </div>
@endsection
