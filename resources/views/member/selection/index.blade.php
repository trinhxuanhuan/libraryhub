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
        @php
            $selectedCopyCount = $books->sum(
                fn ($book) => (int) $book->pivot->quantity
            );
        @endphp

        <div class="card">
            <div class="card-body">

                <h5 class="mb-3">
                    Đã chọn {{ $books->count() }} đầu sách
                    ({{ $selectedCopyCount }} bản)
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

                            <div class="mt-2">
                                <span class="fw-semibold">
                                    Muốn mượn: {{ (int) $book->pivot->quantity }} bản
                                </span>
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
                        <div class="d-flex flex-wrap align-items-end gap-2">

                            {{-- Cập nhật số lượng --}}
                            <form
                                action="{{ route('member.selection.add', $book) }}"
                                method="POST"
                                class="d-flex align-items-end gap-2"
                            >
                                @csrf

                                <div>
                                    <label
                                        for="quantity-{{ $book->id }}"
                                        class="form-label small"
                                    >
                                        Số lượng
                                    </label>

                                    <input
                                        type="number"
                                        name="quantity"
                                        id="quantity-{{ $book->id }}"
                                        class="form-control form-control-sm"
                                        min="1"
                                        max="{{ $available }}"
                                        value="{{ (int) $book->pivot->quantity }}"
                                        style="width: 85px"
                                        required
                                    >
                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-outline-primary"
                                    @disabled($available <= 0)
                                >
                                    Cập nhật
                                </button>
                            </form>

                            {{-- Bỏ chọn sách --}}
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
