
@extends('layouts.app')

@section('content')
<style>
    .book-card {
        transition: transform 0.15s, box-shadow 0.15s;
    }

    .book-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .book-cover {
        height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 32px;
        font-weight: bold;
        overflow: hidden;
    }

    .book-cover img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
        background-color: #f8f9fa;
    }
</style>

<div class="container py-4">

    {{-- Tiêu đề và điều hướng --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Danh mục sách</h2>

        <div class="d-flex gap-2">
            <a href="{{ route('member.loans.index') }}"
               class="btn btn-outline-primary">
                Phiếu mượn của tôi
            </a>

            <a href="{{ route('member.selection.index') }}"
               class="btn btn-outline-primary">
                Sách muốn mượn
            </a>
        </div>
    </div>

    {{-- Tìm kiếm, lọc và sắp xếp --}}
    <form method="GET"
          action="{{ route('member.books.index') }}"
          class="card card-body mb-4">

        <div class="row g-2">
            <div class="col-md-6">
                <input
                    type="search"
                    name="q"
                    class="form-control"
                    value="{{ request('q') }}"
                    placeholder="Tìm theo tiêu đề / tác giả / ISBN"
                >
            </div>

            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">Tất cả thể loại</option>

                    @foreach ($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            @selected(request('category_id') == $category->id)
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <select name="sort" class="form-select">
                    <option value="latest"
                        @selected(request('sort', 'latest') === 'latest')>
                        Mới nhất
                    </option>

                    <option value="title_asc"
                        @selected(request('sort') === 'title_asc')>
                        Tên A-Z
                    </option>

                    <option value="title_desc"
                        @selected(request('sort') === 'title_desc')>
                        Tên Z-A
                    </option>
                </select>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary" type="submit">
                Tìm kiếm
            </button>

            <a href="{{ route('member.books.index') }}"
               class="btn btn-outline-secondary">
                Xóa bộ lọc
            </a>
        </div>
    </form>

    {{-- Thông báo lỗi --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <p class="text-muted">
        Tìm thấy {{ $books->total() }} đầu sách.
    </p>

    {{-- Danh sách sách dạng Card --}}
    <div class="row g-3">
        @forelse ($books as $book)
            @php
                // Số bản khả dụng thực tế
                $available = max(0, min(
                    (int) $book->available_copies,
                    (int) $book->available_copy_count
                ));

                // Chữ viết tắt cho sách chưa có ảnh
                $initials = collect(
                    preg_split('/\s+/u', trim($book->title))
                )
                    ->filter()
                    ->take(2)
                    ->map(fn ($word) => mb_strtoupper(
                        mb_substr($word, 0, 1)
                    ))
                    ->implode('');

                // Màu placeholder
                $colors = [
                    '#6941c6',
                    '#db2777',
                    '#f97316',
                    '#15803d',
                    '#0d9488',
                    '#2563eb',
                ];

                $coverColor = $colors[$book->id % count($colors)];
            @endphp

            <div class="col-6 col-md-4 col-xl-3">
                <div class="card book-card h-100">

                    {{-- Ảnh bìa hoặc placeholder --}}
                    <a href="{{ route('member.books.show', $book) }}"
                       class="text-decoration-none">

                        <div class="book-cover"
                             style="background-color: {{ $coverColor }}">

                            @if ($book->cover_path)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($book->cover_path) }}"
                                    alt="Bìa sách {{ $book->title }}"
                                    loading="lazy"
                                >
                            @else
                                {{ $initials }}
                            @endif

                        </div>
                    </a>

                    {{-- Thông tin sách --}}
                    <div class="card-body d-flex flex-column">

                        <a href="{{ route('member.books.show', $book) }}"
                           class="fw-semibold text-decoration-none text-dark">
                            {{ $book->title }}
                        </a>

                        <small class="text-muted mt-1">
                            {{ $book->authors->pluck('name')->join(', ') }}
                        </small>

                        <div class="mt-2">
                            @if ($available > 0)
                                <span class="badge bg-success">
                                    Còn {{ $available }}
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    Hết sách
                                </span>
                            @endif

                            <span class="badge bg-light text-dark border">
                                {{ $book->category?->name ?? 'Chưa phân loại' }}
                            </span>
                        </div>

                        {{-- Chỉ xem chi tiết, không mượn từ danh mục --}}
                        <div class="mt-auto pt-3">
                            <a href="{{ route('member.books.show', $book) }}"
                               class="btn btn-sm btn-outline-primary w-100 mt-2">
                                Xem chi tiết
                            </a>
                        </div>

                    </div>
                </div>
            </div>

        @empty
            <div class="col-12">
                <div class="alert alert-info">
                    Không tìm thấy sách phù hợp.
                </div>
            </div>
        @endforelse
    </div>

    {{-- Phân trang --}}
    <div class="mt-4">
        {{ $books->links() }}
    </div>

</div>
@endsection
