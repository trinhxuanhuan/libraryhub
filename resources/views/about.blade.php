@extends('layouts.app')

@section('title', 'Giới thiệu — LibraryHub')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1>Giới thiệu LibraryHub</h1>

            <p>{{ $description }}</p>

            <h2 class="h5">Các chức năng dự kiến</h2>

            <ul>
                @foreach ($features as $feature)
                    <li>{{ $feature }}</li>
                @endforeach
            </ul>

            <a href="{{ route('home') }}" class="btn btn-outline-primary">
                Về trang chủ
            </a>
        </div>
    </div>
@endsection