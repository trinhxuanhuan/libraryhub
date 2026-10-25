
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-3">Phiếu mượn của tôi</h2>

    {{-- Thông báo thành công --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Thông báo lỗi --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Quay lại danh sách sách --}}
    <a href="{{ route('member.books.index') }}"
       class="btn btn-primary mb-4">
        Tiếp tục chọn sách
    </a>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Mã phiếu</th>
                    <th>Ngày yêu cầu</th>
                    <th>Danh sách sách</th>
                    <th>Hạn trả</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($loans as $loan)

                    @php
                        // Phiếu đang mượn và đã quá hạn
                        $isOverdue = $loan->status === 'borrowed'
                            && $loan->due_at !== null
                            && $loan->due_at->isPast();

                        // Kiểm tra phiếu có đủ điều kiện gia hạn không
                        $canRenew = $loan->status === 'borrowed'
                            && $loan->due_at?->isFuture()
                            && (int) $loan->renewal_count === 0
                            && $loan->items->isNotEmpty()
                            && $loan->items->every(
                                fn ($item) => $item->returned_at === null
                            );
                    @endphp

                    <tr>
                        {{-- Mã phiếu --}}
                        <td class="fw-semibold">
                            #{{ $loan->id }}
                        </td>

                        {{-- Ngày tạo yêu cầu --}}
                        <td class="text-nowrap">
                            {{ $loan->created_at->format('d/m/Y H:i') }}
                        </td>

                        {{-- Danh sách sách trong phiếu --}}
                        <td>
                            <ul class="mb-0 ps-3">
                                @foreach ($loan->items as $item)
                                    <li class="mb-1">
                                        {{ $item->book?->title ?? 'Đầu sách không còn hiển thị' }}

                                        @if ($item->returned_at !== null)
                                            <span class="badge bg-success">
                                                Đã trả
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </td>

                        {{-- Hạn trả --}}
                        <td class="text-nowrap">
                            @if ($loan->due_at !== null)
                                <div>
                                    {{ $loan->due_at->format('d/m/Y H:i') }}
                                </div>

                                @if ($isOverdue)
                                    <small class="text-danger">
                                        Đã quá hạn
                                    </small>
                                @endif
                            @else
                                <span class="text-muted">
                                    Chưa có
                                </span>
                            @endif
                        </td>

                        {{-- Trạng thái phiếu --}}
                        <td>
                            @if ($loan->status === 'pending')
                                <span class="badge bg-warning text-dark">
                                    Chờ duyệt
                                </span>

                            @elseif ($isOverdue)
                                <span class="badge bg-danger">
                                    Quá hạn
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
                                    Từ chối
                                </span>

                            @else
                                <span class="badge bg-secondary">
                                    {{ $loan->status }}
                                </span>
                            @endif

                            {{-- Hiển thị số lần gia hạn --}}
                            @if ($loan->status === 'borrowed')
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Đã gia hạn:
                                        {{ (int) $loan->renewal_count }}/1 lần
                                    </small>
                                </div>
                            @endif
                        </td>

                        {{-- Thao tác --}}
                        <td class="text-nowrap">
                            @if ($canRenew)
                                <form
                                    method="POST"
                                    action="{{ route('member.loans.renew', $loan) }}"
                                    onsubmit="return confirm('Bạn có chắc muốn gia hạn thêm 7 ngày?')"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Gia hạn 7 ngày
                                    </button>
                                </form>

                            @elseif (
                                $loan->status === 'borrowed'
                                && (int) $loan->renewal_count >= 1
                            )
                                <span class="text-muted small">
                                    Đã hết lượt gia hạn
                                </span>

                            @elseif ($isOverdue)
                                <span class="text-danger small">
                                    Không thể gia hạn
                                </span>

                            @else
                                <span class="text-muted">
                                    —
                                </span>
                            @endif
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="6"
                            class="text-center text-muted py-4">
                            Bạn chưa có phiếu mượn nào.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
