@extends('layouts.app')

@section('content')
<div class="container py-4">

    <h2 class="mb-3">Danh sách sách muốn mượn</h2>

    <a
        href="{{ route('member.books.index') }}"
        class="btn btn-outline-primary mb-3"
    >
        ← Tiếp tục chọn sách
    </a>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if ($books->isEmpty())
        <div class="alert alert-info">
            Bạn chưa chọn cuốn sách nào.
        </div>
    @else
        <div class="card">
            <div class="card-body">

                <h5 class="mb-3">
                    Đã chọn {{ $books->count() }} cuốn sách
                </h5>

                @foreach ($books as $book)
                    @php
                        $available = max(0, min(
                            (int) $book->available_copies,
                            (int) $book->available_copy_count
                        ));
                    @endphp

                    <div class="d-flex justify-content-between
                                align-items-center border-bottom py-3">

                        <div>
                            <a
                                href="{{ route('member.books.show', $book) }}"
                                class="fw-semibold text-decoration-none"
                            >
                                {{ $book->title }}
                            </a>

                            <div class="text-muted small">
                                {{ $book->authors->pluck('name')->join(', ') }}
                            </div>

                            @if ($available > 0)
                                <span class="badge bg-success mt-1">
                                    Còn {{ $available }} bản
                                </span>
                            @else
                                <span class="badge bg-danger mt-1">
                                    Không còn bản khả dụng
                                </span>
                            @endif
                        </div>

                        <form
                            method="POST"
                            action="{{ route('member.selection.remove', $book->id) }}"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn btn-sm btn-outline-danger"
                            >
                                Bỏ chọn
                            </button>
                        </form>
                    </div>
                @endforeach

                <form
                    method="POST"
                    action="{{ route('member.selection.submit') }}"
                    class="mt-4"
                >
                    @csrf

                    <button type="submit" class="btn btn-primary">
                        Gửi yêu cầu mượn {{ $books->count() }} cuốn sách
                    </button>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
