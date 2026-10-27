
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

            {{-- Tìm kiếm và lọc --}}
            <form action="{{ route('admin.books.index') }}"
                  method="GET"
                  class="mb-3">
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

                        <a href="{{ route('admin.books.index') }}"
                           class="btn btn-secondary">
                            Đặt lại
                        </a>
                    </div>
                </div>
            </form>

            @php
                $headers = [
                    'Mã',
                    'Bìa',
                    'ISBN',
                    'Tên sách',
                    'Thể loại',
                    'Tác giả',
                    'Số bản còn lại',
                    'Thao tác',
                ];

                $data = $books->map(function ($book) {
                    // Tạo chữ viết tắt khi sách chưa có ảnh bìa
                    $initials = collect(
                        preg_split('/\s+/u', trim($book->title))
                    )
                        ->filter()
                        ->take(2)
                        ->map(fn ($word) => mb_strtoupper(
                            mb_substr($word, 0, 1)
                        ))
                        ->implode('');

                    // Màu placeholder theo ID sách
                    $colors = [
                        '#6941c6',
                        '#db2777',
                        '#f97316',
                        '#15803d',
                        '#0d9488',
                        '#2563eb',
                    ];

                    $coverColor = $colors[$book->id % count($colors)];

                    // Nếu đã có ảnh thì lấy URL từ public storage
                    if ($book->cover_path) {
                        $coverUrl = \Illuminate\Support\Facades\Storage::disk('public')
                            ->url($book->cover_path);

                        $cover = new \Illuminate\Support\HtmlString(
                            '<img src="' . e($coverUrl) . '"
                                  alt="Bìa sách ' . e($book->title) . '"
                                  class="rounded border"
                                  style="width:46px;height:58px;object-fit:contain;">'
                        );
                    } else {
                        // Chưa có ảnh thì hiển thị placeholder
                        $cover = new \Illuminate\Support\HtmlString(
                            '<div class="rounded d-flex align-items-center
                                         justify-content-center text-white fw-bold"
                                  style="width:46px;height:58px;background-color:'
                            . e($coverColor) . ';">'
                            . e($initials) .
                            '</div>'
                        );
                    }

                    return [
                        $book->id,
                        $cover,
                        $book->isbn,
                        $book->title,
                        $book->category?->name ?? '-',
                        $book->authors->pluck('name')->join(', '),
                        $book->available_copies . ' / ' . $book->total_copies,
                        view('admin.books._actions', ['book' => $book]),
                    ];
                });
            @endphp

            {{-- Bảng danh sách sách --}}
            <x-common.table :headers="$headers" :data="$data" />

            {{-- Phân trang --}}
            <div class="mt-3">
                {{ $books->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
