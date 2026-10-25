@extends('layouts.app')

@section('title', 'Quản lý phiếu mượn — LibraryHub')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">

        {{-- Tiêu đề và nút tạo phiếu mượn --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">Quản lý phiếu mượn</h1>
                <p class="text-muted mb-0">
                    Theo dõi yêu cầu, giao và nhận trả sách.
                </p>
            </div>

            {{-- Nút tạo phiếu mượn --}}
            <a href="{{ route('management.loans.create') }}"
               class="btn btn-primary">
                + Tạo phiếu mượn
            </a>
        </div>

        {{-- Hiển thị lỗi lọc dữ liệu --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        {{-- Bộ lọc phiếu mượn --}}
        <form method="GET"
              action="{{ route('management.loans.index') }}"
              class="border rounded p-3 mb-4 bg-light">

            <div class="row g-3 align-items-end">

                <div class="col-md-6 col-lg-3">
                    <label for="status" class="form-label">
                        Trạng thái
                    </label>

                    <select id="status"
                            name="status"
                            class="form-select">
                        <option value="">Tất cả trạng thái</option>

                        <option value="pending"
                            @selected(request('status') === 'pending')>
                            Chờ xử lý
                        </option>

                        <option value="borrowed"
                            @selected(request('status') === 'borrowed')>
                            Đang mượn
                        </option>

                        <option value="overdue"
                            @selected(request('status') === 'overdue')>
                            Quá hạn
                        </option>

                        <option value="returned"
                            @selected(request('status') === 'returned')>
                            Đã trả
                        </option>

                        <option value="rejected"
                            @selected(request('status') === 'rejected')>
                            Đã từ chối
                        </option>
                    </select>
                </div>

                <div class="col-md-6 col-lg-3">
                    <label for="search" class="form-label">
                        Mã phiếu / Thành viên
                    </label>

                    <input
                        type="search"
                        id="search"
                        name="search"
                        class="form-control"
                        placeholder="Mã phiếu, tên hoặc email"
                        value="{{ request('search') }}"
                    >
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="from_date" class="form-label">
                        Từ ngày yêu cầu
                    </label>

                    <input
                        type="date"
                        id="from_date"
                        name="from_date"
                        class="form-control"
                        value="{{ request('from_date') }}"
                    >
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="to_date" class="form-label">
                        Đến ngày
                    </label>

                    <input
                        type="date"
                        id="to_date"
                        name="to_date"
                        class="form-control"
                        value="{{ request('to_date') }}"
                    >
                </div>

                <div class="col-lg-2">
                    <button type="submit"
                            class="btn btn-primary w-100">
                        Lọc phiếu
                    </button>
                </div>

            </div>

            <div class="mt-3">
                <a href="{{ route('management.loans.index') }}"
                   class="btn btn-sm btn-outline-secondary">
                    Xóa bộ lọc
                </a>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted mb-0">
                Tìm thấy <strong>{{ $loans->total() }}</strong> phiếu mượn
            </p>

            <small class="text-muted">
                15 phiếu / trang
            </small>
        </div>

        {{-- Bảng phiếu mượn --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Thành viên</th>
                        <th>Sách</th>
                        <th>Ngày yêu cầu</th>
                        <th>Ngày giao</th>
                        <th>Hạn trả</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($loans as $loan)
                        @php
                            $isOverdue = $loan->status === 'borrowed'
                                && $loan->due_at !== null
                                && $loan->due_at->isPast();
                        @endphp

                        <tr>
                            <td class="fw-semibold">
                                #{{ $loan->id }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $loan->user?->name ?? 'Không xác định' }}
                                </div>

                                <small class="text-muted">
                                    {{ $loan->user?->email }}
                                </small>
                            </td>

                            <td style="min-width: 180px;">
                                @foreach ($loan->items->take(2) as $item)
                                    <div class="small">
                                        {{ $item->book?->title ?? 'Sách không còn hiển thị' }}
                                    </div>
                                @endforeach

                                @if ($loan->items_count > 2)
                                    <small class="text-muted">
                                        +{{ $loan->items_count - 2 }} đầu sách khác
                                    </small>
                                @endif
                            </td>

                            <td class="text-nowrap">
                                {{ $loan->created_at?->format('d/m/Y') ?? '—' }}
                            </td>

                            <td class="text-nowrap">
                                {{ $loan->borrowed_at?->format('d/m/Y') ?? '—' }}
                            </td>

                            <td class="text-nowrap">
                                {{ $loan->due_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>

                            <td>
                                @if ($isOverdue)
                                    <span class="badge bg-danger">
                                        Quá hạn
                                    </span>

                                @elseif ($loan->status === 'pending')
                                    <span class="badge bg-warning text-dark">
                                        Chờ xử lý
                                    </span>

                                @elseif ($loan->status === 'borrowed')
                                    <span class="badge bg-primary">
                                        Đang mượn
                                    </span>

                                @elseif ($loan->status === 'returned')
                                    <span class="badge bg-success">
                                        Đã trả
                                    </span>

                                @elseif ($loan->status === 'rejected')
                                    <span class="badge bg-secondary">
                                        Đã từ chối
                                    </span>

                                @else
                                    <span class="badge bg-secondary">
                                        {{ $loan->status }}
                                    </span>
                                @endif
                            </td>

                            <td class="text-nowrap">
                                <a href="{{ route('management.loans.show', $loan) }}"
                                   class="btn btn-sm btn-outline-primary">

                                    @if ($loan->status === 'pending')
                                        Xem / Duyệt
                                    @elseif ($loan->status === 'borrowed')
                                        Xem / Trả
                                    @else
                                        Chi tiết
                                    @endif
                                </a>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="8"
                                class="text-center text-muted py-4">
                                Không tìm thấy phiếu mượn phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Phân trang --}}
        <div class="mt-3">
            {{ $loans->links('pagination::bootstrap-5') }}
        </div>

    </div>
</div>
@endsection
