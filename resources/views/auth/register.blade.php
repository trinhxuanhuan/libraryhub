@extends('layouts.app')

@section('title', 'Đăng ký — LibraryHub')

@section('content')
    <section class="lh-auth-wrap py-3 py-lg-4" aria-labelledby="register-heading">
        <div class="card lh-auth-card border-0 shadow-sm overflow-hidden mx-auto">
            <div class="row g-0">
                <div class="col-lg-5">
                    <div class="lh-auth-aside h-100 p-4 p-lg-5 d-flex flex-column justify-content-between">
                        <div class="position-relative lh-auth-aside-content">
                            <span class="lh-eyebrow lh-eyebrow-light mb-4">BẮT ĐẦU CÙNG LIBRARYHUB</span>
                            <h2 class="display-6 fw-bold lh-heading-tight mb-3">
                                Mở ra thế giới<br>của những trang sách.
                            </h2>
                            <p class="lh-auth-aside-description mb-0">
                                Tạo tài khoản thành viên để gửi yêu cầu mượn sách,
                                theo dõi phiếu mượn và quản lý hành trình đọc của bạn.
                            </p>
                        </div>

                        <div class="lh-auth-quote position-relative mt-5">
                            <span class="d-block mb-2 fw-semibold">Khám phá, lựa chọn, rồi bắt đầu đọc.</span>
                            <span class="small">LibraryHub đồng hành cùng mỗi trang sách.</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 bg-white">
                    <div class="lh-auth-form p-4 p-sm-5">
                        <div class="mb-4">
                            <span class="lh-eyebrow text-primary mb-2">THAM GIA THƯ VIỆN</span>
                            <h1 id="register-heading" class="h2 fw-bold mb-2">Đăng ký tài khoản</h1>
                            <p class="text-muted mb-0">Điền thông tin bên dưới để trở thành thành viên LibraryHub.</p>
                        </div>

                        <form action="{{ route('register.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="register-name" class="form-label fw-semibold">Họ và tên</label>
                                <input
                                    type="text"
                                    name="name"
                                    id="register-name"
                                    class="form-control form-control-lg @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}"
                                    autocomplete="name"
                                    maxlength="255"
                                    placeholder="Nguyễn Văn A"
                                    required
                                    autofocus
                                    @error('name') aria-invalid="true" aria-describedby="register-name-error" @enderror
                                >

                                @error('name')
                                    <div id="register-name-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="register-email" class="form-label fw-semibold">Địa chỉ email</label>
                                <input
                                    type="email"
                                    name="email"
                                    id="register-email"
                                    class="form-control form-control-lg @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    inputmode="email"
                                    maxlength="255"
                                    placeholder="ban@example.com"
                                    required
                                    @error('email') aria-invalid="true" aria-describedby="register-email-error" @enderror
                                >

                                @error('email')
                                    <div id="register-email-error" class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="register-password" class="form-label fw-semibold">Mật khẩu</label>
                                <div class="input-group input-group-lg">
                                    <input
                                        type="password"
                                        name="password"
                                        id="register-password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        autocomplete="new-password"
                                        minlength="8"
                                        placeholder="Tối thiểu 8 ký tự"
                                        required
                                        @error('password') aria-invalid="true" aria-describedby="register-password-error" @enderror
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary lh-password-toggle"
                                        data-password-toggle="register-password"
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
                                    <div id="register-password-error" class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="register-password-confirmation" class="form-label fw-semibold">
                                    Xác nhận mật khẩu
                                </label>
                                <div class="input-group input-group-lg">
                                    <input
                                        type="password"
                                        name="password_confirmation"
                                        id="register-password-confirmation"
                                        class="form-control @error('password_confirmation') is-invalid @enderror"
                                        autocomplete="new-password"
                                        minlength="8"
                                        placeholder="Nhập lại mật khẩu"
                                        required
                                        @error('password_confirmation') aria-invalid="true" aria-describedby="register-password-confirmation-error" @enderror
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary lh-password-toggle"
                                        data-password-toggle="register-password-confirmation"
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

                                @error('password_confirmation')
                                    <div id="register-password-confirmation-error" class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                                Tạo tài khoản <span aria-hidden="true">&rarr;</span>
                            </button>
                        </form>

                        <p class="text-center text-muted mt-4 mb-3">
                            Đã có tài khoản?
                            <a class="fw-semibold text-decoration-none" href="{{ route('login') }}">Đăng nhập</a>
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
