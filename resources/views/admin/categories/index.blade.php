@extends('layouts.app')

@section('title', 'Quản lý thể loại — LibraryHub')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <h1 class="h3 mb-3">Quản lý thể loại</h1>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary mb-3">
            Thêm thể loại
        </a>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên thể loại</th>
                        <th>Mô tả</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->description ?? 'Chưa có mô tả' }}</td>
                            <td>
        <div class="d-flex gap-2">
            <a
                href="{{ route('admin.categories.edit', $category) }}"
                class="btn btn-sm btn-outline-primary"
            >
                Sửa
            </a>

            <form
                method="post"
                action="{{ route('admin.categories.destroy', $category) }}"
                onsubmit="return confirm('Bạn có chắc muốn xóa thể loại này?');"
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
                            <td colspan="4" class="text-center text-muted">
                                Chưa có thể loại nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection