@extends('layouts.app')

@section('title', 'Quản lý tác giả — LibraryHub')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-3">Quản lý tác giả</h1>
            <a href="{{route('admin.authors.create')}}"
                class='btn btn-primary mb-3'>
                Thêm tác giả
            </a>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Tên tác giả</th>
                            <th>Tiểu sử</th>
                            <th>Số sách</th>
                            <th>Thao tác</th>
                        </tr> 
                    </thead>
                    <tbody>
                        @forelse ($authors as $author)
                            <tr>
                                <td>{{ $author->id }}</td>
                                <td>{{ $author->name }}</td>
                                <td>{{ $author->bio ?? 'Chưa có tiểu sử' }}</td>
                                <td>{{ $author->books_count }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a
                                            href="{{ route('admin.authors.edit', $author) }}"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Sửa
                                        </a>

                                        <form
                                            method="post"
                                            action="{{ route('admin.authors.destroy', $author) }}"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa tác giả này?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Xóa
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Chưa có tác giả nào.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('admin.categories.index') }}"
               class="btn btn-outline-secondary">
                Quản lý thể loại
            </a>
        </div>
    </div>
@endsection


