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

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>ISBN</th>
                            <th>Tên sách</th>
                            <th>Thể loại</th>
                            <th>Tác giả</th>
                            <th>Số bản còn lại</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($books as $book)
                            <tr>
                                <td>{{ $book->id }}</td>
                                <td>{{ $book->isbn }}</td>
                                <td>{{ $book->title }}</td>
                                <td>{{ $book->category->name }}</td>
                                <td>{{ $book->authors->pluck('name')->join(', ') }}</td>
                                <td>
                                    {{ $book->available_copies }}
                                    /
                                    {{ $book->total_copies }}
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a
                                            href="{{ route('admin.books.edit', $book) }}"
                                            class="btn btn-warning btn-sm"
                                        >
                                            Sửa
                                        </a>

                                        <form
                                            action="{{ route('admin.books.destroy', $book) }}"
                                            method="POST"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa sách này không?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-danger btn-sm">
                                                Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Chưa có sách nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $books->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection
