
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <a href="{{ route('member.books.index') }}"
       class="btn btn-outline-secondary mb-4">
        ← Quay lại danh mục
    </a>

    @php
        $available = max(0, min(
            (int) $book->available_copies,
            (int) $book->available_copy_count
        ));
    @endphp

    <div class="card p-4">
        <div class="row g-4">
            <div class="col-md-4">

                @if ($book->cover_path)
                    {{-- Sách đã có ảnh bìa --}}
                    <div class="rounded border bg-light p-2 text-center">
                        <img
                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($book->cover_path) }}"
                            alt="Bìa sách {{ $book->title }}"
                            class="img-fluid rounded"
                            style="width:100%;max-height:320px;object-fit:contain;"
                        >
                    </div>
                @else
                    {{-- Chưa có ảnh: giữ placeholder --}}
                    @php
                        $initials = collect(
                            preg_split('/\s+/u', trim($book->title))
                        )
                            ->filter()
                            ->take(2)
                            ->map(fn ($word) => mb_strtoupper(
                                mb_substr($word, 0, 1)
                            ))
                            ->implode('');
                    @endphp

                    <div
                        class="rounded d-flex align-items-center justify-content-center text-white"
                        style="height:320px;background:#6941c6;font-size:48px;"
                    >
                        {{ $initials }}
                    </div>
                @endif

            </div>

            <div class="col-md-8">
                <h2>{{ $book->title }}</h2>

                <p>
                    <strong>Tác giả:</strong>
                    {{ $book->authors->pluck('name')->join(', ') }}
                </p>

                <p>
                    <strong>Thể loại:</strong>
                    {{ $book->category?->name ?? 'Chưa phân loại' }}
                </p>

                <p>
                    <strong>ISBN:</strong>
                    {{ $book->isbn }}
                </p>

                <p>
                    <strong>Nhà xuất bản:</strong>
                    {{ $book->publisher }}
                </p>

                <p>
                    <strong>Năm xuất bản:</strong>
                    {{ $book->published_year }}
                </p>

                <p>
                    <strong>Số bản khả dụng:</strong>
                    {{ $available }}
                </p>

                <h5>Mô tả</h5>
                <p>{{ $book->description ?: 'Chưa có mô tả.' }}</p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form
                    action="{{ route('member.selection.add', $book) }}"
                    method="POST"
                >
                    @csrf

                    <button
                        type="submit"
                        class="btn btn-primary"
                        @disabled($available <= 0)
                    >
                        Mượn sách
                    </button>
                </form>

                <a
                    href="{{ route('member.selection.index') }}"
                    class="btn btn-outline-primary mt-2"
                >
                    Xem danh sách sách đã chọn
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
