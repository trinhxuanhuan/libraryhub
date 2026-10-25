@extends('layouts.app')

@section('title', $book->title . ' — LibraryHub')

@section('content')
    @php
        $available = max(0, min(
            (int) $book->available_copies,
            (int) $book->available_copy_count
        ));

        $isMember = $viewer?->role === \App\Enums\UserRole::Member;
        $isGuest = $viewer === null;

        $initials = collect(preg_split('/\s+/u', trim($book->title)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    @endphp

    <section aria-labelledby="book-title">
        <nav aria-label="Điều hướng trang" class="mb-4">
            <a href="{{ route('books.index') }}" class="btn btn-outline-secondary">
                ← Quay lại danh mục sách
            </a>
        </nav>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-body p-3 p-md-4 p-lg-5">
                <div class="row g-4 g-lg-5">
                    <div class="col-md-4">
                        <div class="rounded-3 border bg-light p-3 text-center">
                            @if ($book->cover_path)
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($book->cover_path) }}"
                                    alt="Bìa sách {{ $book->title }}"
                                    class="img-fluid rounded object-fit-contain"
                                    style="width: 100%; max-height: 360px"
                                >
                            @else
                                <div
                                    class="rounded-3 bg-primary bg-gradient d-flex align-items-center justify-content-center text-white fw-bold display-4"
                                    style="height: 320px"
                                    aria-hidden="true"
                                >
                                    {{ $initials }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-8">
                        <h1 id="book-title" class="h2 fw-bold mb-2">{{ $book->title }}</h1>
                        <p class="fs-5 text-muted mb-3">
                            {{ $book->authors->pluck('name')->join(', ') ?: 'Chưa rõ tác giả' }}
                        </p>

                        <div class="mb-4">
                            @if ($available > 0)
                                <span class="badge text-bg-success">Còn {{ $available }} bản có thể mượn</span>
                            @else
                                <span class="badge text-bg-secondary">Hiện đã hết sách</span>
                            @endif
                        </div>

                        <dl class="row mb-4">
                            <dt class="col-sm-4 text-muted fw-normal">Thể loại</dt>
                            <dd class="col-sm-8">{{ $book->category?->name ?? 'Chưa phân loại' }}</dd>

                            <dt class="col-sm-4 text-muted fw-normal">ISBN</dt>
                            <dd class="col-sm-8">{{ $book->isbn ?: 'Chưa cập nhật' }}</dd>

                            <dt class="col-sm-4 text-muted fw-normal">Nhà xuất bản</dt>
                            <dd class="col-sm-8">{{ $book->publisher ?: 'Chưa cập nhật' }}</dd>

                            <dt class="col-sm-4 text-muted fw-normal">Năm xuất bản</dt>
                            <dd class="col-sm-8">{{ $book->published_year ?: 'Chưa cập nhật' }}</dd>
                        </dl>

                        <h2 class="h5 fw-semibold">Giới thiệu sách</h2>
                        <p class="text-secondary">{{ $book->description ?: 'Chưa có mô tả cho cuốn sách này.' }}</p>

                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="d-flex flex-wrap gap-2 align-items-center mt-4">
                            @if ($available <= 0)
                                <button type="button" class="btn btn-secondary" disabled>
                                    Hết sách
                                </button>
                            @elseif ($isMember)
                                <form action="{{ route('member.selection.add', $book) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        Mượn sách
                                    </button>
                                </form>
                            @elseif ($isGuest)
                                {{-- Route có middleware sẽ ghi nhớ trang để trở lại sau đăng nhập. --}}
                                <a href="{{ route('member.books.show', $book) }}" class="btn btn-primary">
                                    Đăng nhập để mượn sách
                                </a>
                            @else
                                <span class="text-muted small">
                                    Chỉ tài khoản Thành viên mới có thể mượn sách.
                                </span>
                            @endif

                            @if ($isMember)
                                <a href="{{ route('member.selection.index') }}" class="btn btn-outline-primary">
                                    Xem danh sách sách đã chọn
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
