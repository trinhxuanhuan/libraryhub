@extends('layouts.app')

@section('title', 'Sách đã chọn — LibraryHub')

@section('content')
    @php
        // Tổng số bản sách Member muốn mượn
        $selectedCopyCount = (int) $books->sum(
            fn ($book) => (int) $book->pivot->quantity
        );

        // Kiểm tra có đầu sách nào không đủ số bản khả dụng
        $hasInsufficientStock = $books->contains(function ($book) {
            $available = max(0, min(
                (int) $book->available_copies,
                (int) $book->available_copy_count
            ));

            return (int) $book->pivot->quantity > $available;
        });
    @endphp

    {{-- Tiêu đề và điều hướng --}}
    <div class="d-flex flex-column flex-sm-row
                justify-content-between align-items-sm-center gap-3 mb-4">

        <div>
            <h1 class="h3 fw-bold mb-1">
                Danh sách sách muốn mượn
            </h1>

            <p class="text-muted mb-0">
                Kiểm tra và điều chỉnh số lượng trước khi gửi yêu cầu.
            </p>
        </div>

        <a
            href="{{ route('books.index') }}"
            class="btn btn-outline-primary"
        >
            ← Tiếp tục chọn sách
        </a>
    </div>

    {{-- Hiển thị lỗi validation --}}
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2">
                Không thể thực hiện yêu cầu
            </div>

            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Danh sách trống --}}
    @if ($books->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">

                <div class="fs-1 mb-3" aria-hidden="true">
                    📚
                </div>

                <h2 class="h5 fw-semibold mb-2">
                    Bạn chưa chọn cuốn sách nào
                </h2>

                <p class="text-muted mb-4">
                    Hãy khám phá danh mục sách và chọn những cuốn
                    bạn muốn mượn.
                </p>

                <a
                    href="{{ route('books.index') }}"
                    class="btn btn-primary"
                >
                    Khám phá sách
                </a>
            </div>
        </div>
    @else

        {{-- Thống kê sách đã chọn --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">

                <div class="d-flex flex-column flex-sm-row
                            justify-content-between gap-3">

                    <div>
                        <h2 class="h5 fw-semibold mb-2">
                            Sách đã chọn
                        </h2>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge text-bg-primary">
                                {{ $books->count() }} đầu sách
                            </span>

                            <span class="badge text-bg-secondary">
                                {{ $selectedCopyCount }} bản muốn mượn
                            </span>
                        </div>
                    </div>

                    <div class="text-sm-end">
                        <span class="text-muted small">
                            Bạn có thể thay đổi số lượng
                            trước khi gửi yêu cầu.
                        </span>
                    </div>
                </div>

            </div>
        </div>

        {{-- Cảnh báo số lượng không còn đáp ứng --}}
        @if ($hasInsufficientStock)
            <div class="alert alert-warning" role="alert">
                <div class="fw-semibold mb-1">
                    Số lượng sách không còn đủ
                </div>

                <div>
                    Một số đầu sách hiện không còn đủ số bản bạn đã chọn.
                    Vui lòng cập nhật số lượng hoặc bỏ chọn trước khi
                    gửi yêu cầu mượn.
                </div>
            </div>
        @endif

        {{-- Danh sách từng đầu sách --}}
        <div class="card border-0 shadow-sm overflow-hidden mb-4">

            <div class="card-header bg-white py-3 px-3 px-md-4">
                <h2 class="h5 fw-semibold mb-0">
                    Chi tiết danh sách
                </h2>
            </div>

            <div class="card-body p-0">

                @foreach ($books as $book)
                    @php
                        // Số bản vật lý thực tế có thể mượn
                        $available = max(0, min(
                            (int) $book->available_copies,
                            (int) $book->available_copy_count
                        ));

                        // Số lượng Member đã chọn
                        $selectedQuantity = (int) $book->pivot->quantity;

                        // Có đủ bản để đáp ứng yêu cầu không?
                        $insufficientStock = $selectedQuantity > $available;

                        // Tạo chữ viết tắt nếu sách chưa có ảnh bìa
                        $initials = collect(
                            preg_split('/\s+/u', trim($book->title))
                        )
                            ->filter()
                            ->take(2)
                            ->map(
                                fn ($word) => mb_strtoupper(
                                    mb_substr($word, 0, 1)
                                )
                            )
                            ->implode('');
                    @endphp

                    <div class="p-3 p-md-4
                                {{ ! $loop->last ? 'border-bottom' : '' }}">

                        <div class="row g-3 align-items-center">

                            {{-- Ảnh bìa sách --}}
                            <div class="col-auto">
                                <a
                                    href="{{ route('books.show', $book) }}"
                                    class="text-decoration-none"
                                >
                                    <div
                                        class="rounded border bg-light
                                               d-flex align-items-center
                                               justify-content-center overflow-hidden"
                                        style="width: 80px; height: 110px"
                                    >
                                        @if ($book->cover_path)
                                            <img
                                                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($book->cover_path) }}"
                                                alt="Bìa sách {{ $book->title }}"
                                                class="w-100 h-100 object-fit-contain"
                                            >
                                        @else
                                            <div
                                                class="w-100 h-100 bg-primary
                                                       bg-gradient text-white
                                                       d-flex align-items-center
                                                       justify-content-center
                                                       fw-bold fs-4"
                                                aria-hidden="true"
                                            >
                                                {{ $initials }}
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            </div>

                            {{-- Thông tin sách --}}
                            <div class="col">

                                <h3 class="h6 fw-semibold mb-1">
                                    <a
                                        href="{{ route('books.show', $book) }}"
                                        class="text-decoration-none"
                                    >
                                        {{ $book->title }}
                                    </a>
                                </h3>

                                <p class="text-muted small mb-2">
                                    {{ $book->authors->pluck('name')->join(', ') ?: 'Chưa rõ tác giả' }}
                                </p>

                                <div class="d-flex flex-wrap gap-2 mb-2">

                                    @if ($available > 0)
                                        <span class="badge text-bg-success">
                                            Còn {{ $available }} bản
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary">
                                            Hiện đã hết sách
                                        </span>
                                    @endif

                                    <span class="badge text-bg-light border">
                                        Đã chọn {{ $selectedQuantity }} bản
                                    </span>
                                </div>

                                @if ($insufficientStock)
                                    <div class="text-danger small">
                                        Số lượng đã chọn vượt quá
                                        số bản hiện có.
                                    </div>
                                @endif
                            </div>

                            {{-- Thao tác cập nhật số lượng --}}
                            <div class="col-12 col-lg-5">

                                <div class="d-flex flex-wrap
                                            justify-content-lg-end
                                            align-items-end gap-2">

                                    <form
                                        method="POST"
                                        action="{{ route('member.selection.add', $book) }}"
                                        class="d-flex align-items-end gap-2"
                                    >
                                        @csrf

                                        <div>
                                            <label
                                                for="quantity-{{ $book->id }}"
                                                class="form-label small fw-semibold mb-1"
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
                                                value="{{ $selectedQuantity }}"
                                                style="width: 90px"
                                                required
                                                @disabled($available === 0)
                                            >
                                        </div>

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-primary"
                                            @disabled($available === 0)
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

                        </div>
                    </div>
                @endforeach

            </div>
        </div>

        {{-- Tổng kết và gửi yêu cầu --}}
        <div class="card border-0 shadow-sm">

            <div class="card-body p-3 p-md-4">

                <div class="d-flex flex-column flex-md-row
                            justify-content-between
                            align-items-md-center gap-3">

                    <div>
                        <h2 class="h5 fw-semibold mb-2">
                            Tổng kết yêu cầu
                        </h2>

                        <div class="text-muted">
                            Số đầu sách:
                            <span class="fw-semibold text-dark">
                                {{ $books->count() }}
                            </span>
                        </div>

                        <div class="text-muted">
                            Tổng số bản muốn mượn:
                            <span class="fw-semibold text-dark">
                                {{ $selectedCopyCount }}
                            </span>
                        </div>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('member.selection.submit') }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="btn btn-primary px-4"
                            @disabled($hasInsufficientStock)
                        >
                            Gửi yêu cầu mượn {{ $selectedCopyCount }} bản
                        </button>
                    </form>

                </div>

                @if ($hasInsufficientStock)
                    <p class="text-danger small mt-3 mb-0">
                        Vui lòng điều chỉnh số lượng sách không còn đủ
                        trước khi gửi yêu cầu.
                    </p>
                @endif

            </div>
        </div>

    @endif
@endsection
