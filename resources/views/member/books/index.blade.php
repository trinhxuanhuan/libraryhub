@extends('layouts.app')

@section('title', 'Danh mục sách — LibraryHub')

@section('content')
    @php
        $isMember = $viewer?->role === \App\Enums\UserRole::Member;
        $coverColors = ['#6941c6', '#db2777', '#f97316', '#15803d', '#0d9488', '#2563eb'];
    @endphp

    <section class="catalog-page" aria-labelledby="catalog-title">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h1 id="catalog-title" class="h2 fw-bold mb-2">Khám phá sách</h1>
                <p class="text-muted mb-0">
                    Tìm cuốn sách phù hợp với bạn từ bộ sưu tập của LibraryHub.
                </p>
            </div>

            @if ($isMember)
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('member.selection.index') }}" class="btn btn-primary">
                        Sách đã chọn
                    </a>
                    <a href="{{ route('member.loans.index') }}" class="btn btn-outline-primary">
                        Phiếu mượn của tôi
                    </a>
                </div>
            @endif
        </div>

        {{-- Tìm kiếm, lọc và sắp xếp đều dùng cùng một URL công khai. --}}
        <form method="GET" action="{{ route('books.index') }}" class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="catalog-q" class="form-label fw-semibold">Tìm kiếm</label>
                        <input
                            id="catalog-q"
                            type="search"
                            name="q"
                            class="form-control"
                            value="{{ old('q', request('q')) }}"
                            placeholder="Tên sách, tác giả hoặc ISBN"
                            maxlength="150"
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="catalog-category" class="form-label fw-semibold">Thể loại</label>
                        <select id="catalog-category" name="category_id" class="form-select">
                            <option value="">Tất cả thể loại</option>
                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category->id }}"
                                    @selected((string) request('category_id') === (string) $category->id)
                                >
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="catalog-sort" class="form-label fw-semibold">Sắp xếp</label>
                        <select id="catalog-sort" name="sort" class="form-select">
                            <option value="latest" @selected(request('sort', 'latest') === 'latest')>
                                Mới nhất
                            </option>
                            <option value="title_asc" @selected(request('sort') === 'title_asc')>
                                Tên A–Z
                            </option>
                            <option value="title_desc" @selected(request('sort') === 'title_desc')>
                                Tên Z–A
                            </option>
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">Tìm sách</button>
                    <a href="{{ route('books.index') }}" class="btn btn-outline-secondary">Xóa bộ lọc</a>
                </div>
            </div>
        </form>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="text-muted mb-3">
            Tìm thấy <strong>{{ number_format($books->total()) }}</strong> đầu sách.
        </p>

        <div class="row g-3 g-lg-4">
            @forelse ($books as $book)
                @php
                    $available = max(0, min(
                        (int) $book->available_copies,
                        (int) $book->available_copy_count
                    ));

                    $initials = collect(preg_split('/\s+/u', trim($book->title)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                        ->implode('');

                    $coverColor = $coverColors[$book->id % count($coverColors)];
                @endphp

                <div class="col-6 col-md-4 col-xl-3">
                    <article class="card h-100 border-0 shadow-sm overflow-hidden">
                        <a
                            href="{{ route('books.show', $book) }}"
                            class="text-decoration-none"
                            aria-label="Xem chi tiết {{ $book->title }}"
                        >
                            <div class="ratio ratio-4x3 bg-light">
                                @if ($book->cover_path)
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($book->cover_path) }}"
                                        alt="Bìa sách {{ $book->title }}"
                                        class="w-100 h-100 object-fit-contain"
                                        loading="lazy"
                                    >
                                @else
                                    <div
                                        class="d-flex align-items-center justify-content-center text-white fs-2 fw-bold"
                                        style="background-color: {{ $coverColor }}"
                                        aria-hidden="true"
                                    >
                                        {{ $initials }}
                                    </div>
                                @endif
                            </div>
                        </a>

                        <div class="card-body d-flex flex-column">
                            <h2 class="h6 mb-1">
                                <a
                                    href="{{ route('books.show', $book) }}"
                                    class="text-decoration-none text-dark fw-semibold"
                                >
                                    {{ $book->title }}
                                </a>
                            </h2>

                            <p class="small text-muted mb-2">
                                {{ $book->authors->pluck('name')->join(', ') ?: 'Chưa rõ tác giả' }}
                            </p>

                            <div class="d-flex flex-wrap gap-1 mb-3">
                                @if ($available > 0)
                                    <span class="badge text-bg-success">Còn {{ $available }} bản</span>
                                @else
                                    <span class="badge text-bg-secondary">Hết sách</span>
                                @endif

                                <span class="badge bg-light text-dark border">
                                    {{ $book->category?->name ?? 'Chưa phân loại' }}
                                </span>
                            </div>

                            <a
                                href="{{ route('books.show', $book) }}"
                                class="btn btn-sm btn-outline-primary w-100 mt-auto"
                            >
                                Xem chi tiết
                            </a>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-center p-4 p-md-5">
                        <h2 class="h5">Không tìm thấy sách phù hợp</h2>
                        <p class="text-muted">Hãy thử từ khóa hoặc thể loại khác.</p>
                        <a href="{{ route('books.index') }}" class="btn btn-outline-primary mx-auto">
                            Xem tất cả sách
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($books->hasPages())
            <div class="mt-4">
                {{ $books->links() }}
            </div>
        @endif
    </section>
@endsection
