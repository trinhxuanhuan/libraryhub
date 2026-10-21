@extends('layouts.app')

@section('title', 'Quản lý sách — LibraryHub')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-3">Quản lý sách</h1>

            <a href="{{ route('admin.books.create') }}"
               class="btn btn-primary mb-3">
                Thêm sách
            </a>
            <form action="{{ route('admin.books.index') }}" method="GET" class="mb-3">
                <div class="row g-2">

                    <div class="col-md-5">
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Nhập tên sách hoặc ISBN"
                            value="{{ request()->query('search', '') }}"
                        >
                    </div>

                    <div class="col-md-3">
                        <select name="category_id" class="form-select">
                            <option value="">Tất cả thể loại</option>

                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category->id }}"
                                    @selected(request()->query('category_id') == $category->id)
                                >
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            Tìm kiếm / Lọc
                        </button>

                        <a href="{{ route('admin.books.index') }}" class="btn btn-secondary">
                            Đặt lại
                        </a>
                    </div>

                </div>
            </form>

            @php
                    $headers = [
                        'Mã',
                        'ISBN',
                        'Tên sách',
                        'Thể loại',
                        'Tác giả',
                        'Số bản còn lại',
                        'Thao tác',
                    ];

                    $data = $books->map(function ($book) {
                        return [
                            $book->id,
                            $book->isbn,
                            $book->title,
                            $book->category?->name ?? '-',
                            $book->authors->pluck('name')->join(', '),
                            $book->available_copies . ' / ' . $book->total_copies,
                            view('admin.books._actions', ['book' => $book]),
                        ];
                    });
                @endphp

                <x-common.table :headers="$headers" :data="$data" />

                <div class="mt-3">
                    {{ $books->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection
