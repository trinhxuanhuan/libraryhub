@extends('layouts.app')

@section('title', 'Trang chủ — LibraryHub')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1>Chào mừng đến LibraryHub</h1>

            <p>Tra cứu sách và quản lý hoạt động mượn trả</p>

            <a href="{{ route('about') }}" class="btn btn-primary">
                Tìm hiểu về LibraryHub
            </a>
        </div>
    </div>
@endsection
