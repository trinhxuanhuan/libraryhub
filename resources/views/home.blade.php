@extends('layouts.app')

@section('title', 'Trang chủ — LibraryHub')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1>Chào mừng đến {{ $appName }}</h1>

            <p>{{ $description }}</p>

            <a href="{{ route('about') }}" class="btn btn-primary">
                Tìm hiểu về LibraryHub
            </a>
        </div>
    </div>
@endsection