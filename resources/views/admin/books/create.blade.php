@extends('layouts.app')

@section('title', 'Thêm sách — LibraryHub')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-3">Thêm sách</h1>

            <form method="POST" action="{{ route('admin.books.store') }}"  enctype="multipart/form-data">
                @csrf
                @include('admin.books._form')

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    Thêm sách
                </button>

                <a href="{{ route('admin.books.index') }}" class="btn btn-secondary">
                    Quay lại
                </a>
            </div>
            </form>
        </div>
    </div>
@endsection
