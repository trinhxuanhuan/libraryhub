@extends('layouts.app')

@section('title', 'Giới thiệu — LibraryHub')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1>Giới thiệu LibraryHub</h1>

            <p>LibraryHub hỗ trợ tra cứu sách và quản lý hoạt động thư viện</p>

            <h2 class="h5">Các chức năng dự kiến</h2>

            <ul>
                <li>Tra cứu thông tin sách.</li>
                <li>Quản lý sách, tác giả và thể loại.</li>
                <li>Theo dõi việc mượn, gia hạn và trả sách.</li>
            </ul>

            <a href="{{ route('home') }}" class="btn btn-outline-primary">
                Về trang chủ
            </a>
        </div>
    </div>
@endsection
