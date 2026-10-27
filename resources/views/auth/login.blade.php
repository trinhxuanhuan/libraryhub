@extends('layouts.app')

@section('title', 'Đăng nhập — LibraryHub')

@section('content')
    <section class="lh-auth-wrap py-3 py-lg-4" aria-labelledby="login-heading">
        <div class="card lh-auth-card border-0 shadow-sm overflow-hidden mx-auto">
            <div class="row g-0">
                <div class="col-lg-5">
                    <div class="lh-auth-aside h-100 p-4 p-lg-5 d-flex flex-column justify-content-between">
                        <div class="position-relative lh-auth-aside-content">
                            <span class="lh-eyebrow lh-eyebrow-light mb-4">LIBRARYHUB</span>
                            <h2 class="display-6 fw-bold lh-heading-tight mb-3">
                                Chào mừng<br>trở lại!
                            </h2>
                            <p class="lh-auth-aside-description mb-0">
                                Những cuốn sách thú vị vẫn đang chờ bạn. Đăng nhập
                                để chọn sách và quản lý các phiếu mượn của mình.
                            </p>
                        </div>

                        <div class="lh-auth-quote position-relative mt-5">
                            <span class="d-block mb-2 fw-semibold">Đọc thêm một trang, biết thêm một điều.</span>
                            <span class="small">Hành trình khám phá tri thức cùng LibraryHub.</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 bg-white">
                    <div class="lh-auth-form p-4 p-sm-5">
                        <div class="mb-4">
                            <span class="lh-eyebrow text-primary mb-2">TÀI KHOẢN CỦA BẠN</span>
                            <h1 id="login-heading" class="h2 fw-bold mb-2">Đăng nhập</h1>
                            <p class="text-muted mb-0">Vui lòng nhập thông tin tài khoản để tiếp tục.</p>
                        </div>

                        <form action="{{ route('login.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="login-email" class="form-label fw-semibold">Địa chỉ email</label>
                                <input
                                    type="email"
                                    name="email"
                                    id="login-email"
                                    class="form-control form-control-lg @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}"
                                    autocomplete="username"
                                    inputmode="email"
                                    maxlength="255"
                                    placeholder="ban@example.com"
                                    required
                                    autofocus
                                    @error('email') aria-invalid="true" aria-describedby="login-email-error" @enderror
                                >

                                @error('email')
                                    <div id="login-email-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="login-password" class="form-label fw-semibold">Mật khẩu</label>
                                <div class="input-group input-group-lg">
                                    <input
                                        type="password"
                                        name="password"
                                        id="login-password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        autocomplete="current-password"
                                        placeholder="Nhập mật khẩu"
                                        required
                                        @error('password') aria-invalid="true" aria-describedby="login-password-error" @enderror
                                    >

                                    {{-- Giữ data-password-toggle để dùng JS hiện/ẩn mật khẩu đang có. --}}
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary lh-password-toggle"
                                        data-password-toggle="login-password"
                                        aria-label="Hiện mật khẩu"
                                        aria-pressed="false"
                                    >
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7Z" />
                                            <circle cx="12" cy="12" r="3" />
                                            <path data-eye-slash d="M3 3l18 18" />
                                        </svg>
                                    </button>
                                </div>

                                @error('password')
                                    <div id="login-password-error" class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                                Đăng nhập <span aria-hidden="true">&rarr;</span>
                            </button>
                        </form>

                        <p class="text-center text-muted mt-4 mb-3">
                            Chưa có tài khoản?
                            <a class="fw-semibold text-decoration-none" href="{{ route('register') }}">Đăng ký ngay</a>
                        </p>

                        <div class="text-center border-top pt-3">
                            <a class="text-decoration-none small" href="{{ route('books.index') }}">
                                <span aria-hidden="true">&larr;</span> Xem danh mục sách không cần đăng nhập
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
