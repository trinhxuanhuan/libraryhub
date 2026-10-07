@extends('layouts.app')

@section('title', 'Thêm thể loại — LibraryHub')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <h1 class="h3 mb-4">Thêm thể loại</h1>

        <form method="post" action="{{ route('admin.categories.store') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Tên thể loại *</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}"
                    maxlength="100"
                    required
                >

                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Mô tả</label>

                <textarea
                    id="description"
                    name="description"
                    class="form-control @error('description') is-invalid @enderror"
                    rows="4"
                >{{ old('description') }}</textarea>

                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Lưu thể loại</button>

            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">
                Hủy
            </a>
        </form>
    </div>
</div>
@endsection