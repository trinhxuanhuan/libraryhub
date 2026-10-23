@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h1 class="mb-4">Quản lý người dùng</h1>

        @php
            $headers = [
                'ID',
                'Họ tên',
                'Email',
                'Vai trò',
                'Trạng thái',
                'Thao tác',
            ];

            $data = $users->getCollection()->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role->name,
                    $user->is_active ? 'Hoạt động' : 'Đã khóa',
                    view('admin.users._actions', ['user' => $user]),
                ];
            });
        @endphp

        <x-common.table :headers="$headers" :data="$data" />

        {{ $users->links('pagination::bootstrap-5') }}
    </div>
@endsection
