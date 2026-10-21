@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h1 class="mb-4">Chỉnh sửa người dùng</h1>

        <div class="mb-3">
            <strong>Họ tên:</strong> {{ $user->name }}
        </div>

        <div class="mb-3">
            <strong>Email:</strong> {{ $user->email }}
        </div>

        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="role" class="form-label">Vai trò</label>

                <select name="role" id="role"
                    class="form-select @error('role') is-invalid @enderror">

                    @foreach (\App\Enums\UserRole::cases() as $role)
                        <option value="{{ $role->value }}"
                            @selected(old('role', $user->role->value) === $role->value)>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>

                @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="is_active" class="form-label">
                    Trạng thái tài khoản
                </label>

                <select name="is_active" id="is_active"
                    class="form-select @error('is_active') is-invalid @enderror">

                    <option value="1"
                        @selected((string) old('is_active', (int) $user->is_active) === '1')>
                        Hoạt động
                    </option>

                    <option value="0"
                        @selected((string) old('is_active', (int) $user->is_active) === '0')>
                        Đã khóa
                    </option>
                </select>

                @error('is_active')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">
                Lưu thay đổi
            </button>

            <a href="{{ route('admin.users.index') }}"
                class="btn btn-secondary">
                Quay lại
            </a>
        </form>
    </div>
@endsection
