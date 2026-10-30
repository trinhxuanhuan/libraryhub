@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân — LibraryHub')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">

        <h1 class="h3 mb-4">Hồ sơ cá nhân</h1>

        {{-- Thông tin cá nhân --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-4">Thông tin cá nhân</h2>

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Họ và tên
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}"
                            required
                        >

                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            class="form-control"
                            value="{{ $user->email }}"
                            readonly
                        >

                        <div class="form-text">
                            Không thể thay đổi email tại đây.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">
                            Số điện thoại <span class="text-danger">*</span>
                        </label>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $user->phone) }}"
                            placeholder="Ví dụ: 0912345678"
                            maxlength="10"
                            inputmode="tel"
                            autocomplete="tel"
                            required
                        >

                        @error('phone')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Lưu thay đổi
                    </button>
                </form>
            </div>
        </div>

        {{-- Đổi mật khẩu --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-4">Đổi mật khẩu</h2>

                <form method="POST" action="{{ route('profile.password.request') }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="current_password" class="form-label">
                            Mật khẩu hiện tại
                        </label>

                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            class="form-control @error('current_password') is-invalid @enderror"
                            autocomplete="current-password"
                            required
                        >

                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Mật khẩu mới
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">
                            Xác nhận mật khẩu mới
                        </label>

                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-outline-primary">
                        Đổi mật khẩu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
