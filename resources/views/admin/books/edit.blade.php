@extends('layouts.app')

@section('title', 'Sửa sách — LibraryHub')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-3">Sửa sách</h1>

            <form
                action="{{ route('admin.books.update', $book) }}"
                method="POST"
                enctype="multipart/form-data"
                novalidate
            >
                @csrf
                @method('PUT')

                @include('admin.books._form')

                <button type="submit" class="btn btn-primary">
                    Lưu thay đổi
                </button>

                <a href="{{ route('admin.books.index') }}"
                   class="btn btn-secondary">
                    Quay lại
                </a>
            </form>
        </div>
    </div>
@endsection
