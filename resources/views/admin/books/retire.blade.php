@extends('layouts.app')

@section('title', 'Ngừng cung cấp sách — LibraryHub')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h1 class="h3 mb-3">Ngừng cung cấp đầu sách</h1>

        <p>
            Bạn đang chuẩn bị ngừng cung cấp:
            <strong>{{ $book->title }}</strong>
        </p>

        <div class="alert alert-warning">
            <strong>Lưu ý:</strong>

            <ul class="mb-0 mt-2">
                <li>
                    Có {{ $pendingLoansCount }} phiếu đang chờ duyệt
                    chứa đầu sách này.
                </li>

                <li>
                    Nếu xác nhận, toàn bộ các phiếu đó sẽ bị từ chối.
                </li>

                <li>
                    Các phiếu đang mượn vẫn được tiếp tục xử lý trả sách.
                </li>

                <li>
                    Lịch sử mượn/trả vẫn được giữ lại.
                </li>
            </ul>
        </div>

        <p class="text-muted small">
            Số phiếu bị ảnh hưởng sẽ được kiểm tra lại
            khi bạn xác nhận thao tác.
        </p>

        <div class="d-flex flex-wrap gap-2">
            <form
                action="{{ route('admin.books.destroy', $book) }}"
                method="POST"
            >
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    Xác nhận ngừng cung cấp
                </button>
            </form>

            <a
                href="{{ route('admin.books.index') }}"
                class="btn btn-outline-secondary"
            >
                Hủy thao tác
            </a>
        </div>
    </div>
</div>
@endsection
