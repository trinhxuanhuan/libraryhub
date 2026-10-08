@extends('layouts.app')

@section('title', 'Sửa tác giả — LibraryHub')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h3 mb-3">Sửa tác giả</h1>

            <form method="post"
                  action="{{ route('admin.authors.update', $author) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Tên tác giả</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $author->name) }}"
                        maxlength="150"
                        required
                        class="form-control @error('name') is-invalid @enderror"
                    >

                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="bio" class="form-label">Tiểu sử</label>

                    <textarea
                        id="bio"
                        name="bio"
                        rows="4"
                        class="form-control @error('bio') is-invalid @enderror"
                    >{{ old('bio', $author->bio) }}</textarea>

                    @error('bio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    Cập nhật tác giả
                </button>

                <a href="{{ route('admin.authors.index') }}"
                   class="btn btn-outline-secondary">
                    Hủy
                </a>
            </form>
        </div>
    </div>
@endsection
