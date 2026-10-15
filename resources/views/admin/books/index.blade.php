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
                                    <a
                                        href="{{ route('admin.books.edit', $book) }}"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Sửa
                                    </a>
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
            </div>
        </div>
    </div>
@endsection
