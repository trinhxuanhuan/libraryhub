
@extends('layouts.app')

@section('title', 'Chi tiết phiếu mượn — LibraryHub')

@section('content')
<div class="container py-4">
    @php
        $isOverdue = $loan->status === 'borrowed'
            && $loan->due_at !== null
            && $loan->due_at->isPast();

        $totalOverdueFine = $loan->items->sum(
            fn ($item) => (int) $item->overdue_fine_amount
        );

        $totalCompensation = $loan->items->sum(
            fn ($item) => (int) $item->compensation_amount
        );

        $totalFine = $totalOverdueFine + $totalCompensation;
    @endphp

    <div class="card shadow-sm">
        <div class="card-body">

            {{-- Tiêu đề và quay lại --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    Phiếu mượn #{{ $loan->id }}
                </h1>

                <a href="{{ route('librarian.loans.index') }}"
                   class="btn btn-outline-secondary">
                    Quay lại
                </a>
            </div>

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

            {{-- Thông tin phiếu mượn --}}
            <div class="border rounded p-3 mb-4">
                <h2 class="h5 mb-3">Thông tin phiếu mượn</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Người mượn:</strong>
                        {{ $loan->user?->name ?? 'Không xác định' }}
                    </div>

                    <div class="col-md-6">
                        <strong>Email:</strong>
                        {{ $loan->user?->email ?? '—' }}
                    </div>

                    <div class="col-md-6">
                        <strong>Ngày yêu cầu:</strong>
                        {{ $loan->created_at?->format('d/m/Y H:i') ?? '—' }}
                    </div>

                    <div class="col-md-6">
                        <strong>Trạng thái:</strong>

                        @if ($loan->status === 'pending')
                            <span class="badge bg-warning text-dark">
                                Chờ xử lý
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
                                Đã hoàn tất
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
                    </div>

                    <div class="col-md-6">
                        <strong>Ngày giao:</strong>
                        {{ $loan->borrowed_at?->format('d/m/Y H:i') ?? '—' }}
                    </div>

                    <div class="col-md-6">
                        <strong>Hạn trả:</strong>

                        @if ($loan->due_at)
                            {{ $loan->due_at->format('d/m/Y H:i') }}

                            @if ($isOverdue)
                                <span class="text-danger ms-2">
                                    (Đã quá hạn)
                                </span>
                            @endif
                        @else
                            —
                        @endif
                    </div>

                    <div class="col-md-6">
                        <strong>Ngày hoàn tất phiếu:</strong>
                        {{ $loan->returned_at?->format('d/m/Y H:i') ?? '—' }}
                    </div>

                    <div class="col-md-6">
                        <strong>Số lần gia hạn:</strong>
                        {{ (int) $loan->renewal_count }}/1
                    </div>
                </div>
            </div>

            {{-- Danh sách sách --}}
            <h2 class="h5 mb-3">Danh sách sách trong phiếu</h2>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Đầu sách</th>
                            <th>Bản sách / Barcode</th>
                            <th>Tình trạng</th>
                            <th>Phí phát sinh</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($loan->items as $item)
                            @php
                                $itemOverdueFine = (int) $item->overdue_fine_amount;
                                $itemCompensation = (int) $item->compensation_amount;
                                $itemTotalFine = $itemOverdueFine + $itemCompensation;
                            @endphp

                            <tr>
                                {{-- Tên đầu sách --}}
                                <td>
                                    <strong>
                                        {{ $item->book?->title ?? 'Sách không còn hiển thị' }}
                                    </strong>
                                </td>

                                {{-- Chọn BookCopy khi đang chờ duyệt --}}
                                <td>
                                    @if ($loan->status === 'pending')
                                        @php
                                            $copies = $availableCopiesByBook
                                                ->get($item->book_id, collect());
                                        @endphp

                                        <select
                                            name="copies[{{ $item->id }}]"
                                            form="issue-loan-form"
                                            class="form-select form-select-sm"
                                            required
                                        >
                                            <option value="">
                                                -- Chọn bản sách --
                                            </option>

                                            @foreach ($copies as $copy)
                                                <option
                                                    value="{{ $copy->id }}"
                                                    @selected(
                                                        old('copies.' . $item->id) == $copy->id
                                                    )
                                                >
                                                    {{ $copy->barcode }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @if ($copies->isEmpty())
                                            <small class="text-danger">
                                                Không còn bản sách khả dụng.
                                            </small>
                                        @endif

                                    @else
                                        {{ $item->bookCopy?->barcode ?? 'Chưa giao' }}
                                    @endif
                                </td>

                                {{-- Trạng thái xử lý --}}
                                <td>
                                    @if ($item->resolved_at !== null)

                                        @if ($item->return_condition === 'lost')
                                            <span class="badge bg-danger">
                                                Đã xác nhận mất
                                            </span>

                                        @elseif ($item->return_condition === 'damaged')
                                            <span class="badge bg-warning text-dark">
                                                Đã trả - Bị hỏng
                                            </span>

                                        @else
                                            <span class="badge bg-success">
                                                Đã trả - Bình thường
                                            </span>
                                        @endif

                                        <div class="small text-muted mt-1">
                                            Xử lý lúc:
                                            {{ $item->resolved_at->format('d/m/Y H:i') }}
                                        </div>

                                        @if ($item->return_note)
                                            <div class="small mt-2">
                                                <strong>Ghi chú:</strong>
                                                {{ $item->return_note }}
                                            </div>
                                        @endif

                                    @elseif ($item->returned_at !== null)
                                        <span class="badge bg-success">
                                            Đã trả
                                        </span>

                                    @elseif ($loan->status === 'borrowed')
                                        <span class="badge bg-warning text-dark">
                                            Chưa trả
                                        </span>

                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- Phí phạt và bồi thường --}}
                                <td class="text-nowrap">
                                    @if ($item->resolved_at !== null)
                                        <div class="small">
                                            Phí quá hạn:
                                            <strong>
                                                {{ number_format($itemOverdueFine, 0, ',', '.') }} đ
                                            </strong>
                                        </div>

                                        <div class="small">
                                            Bồi thường:
                                            <strong>
                                                {{ number_format($itemCompensation, 0, ',', '.') }} đ
                                            </strong>
                                        </div>

                                        <div class="mt-2">
                                            <strong class="{{ $itemTotalFine > 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($itemTotalFine, 0, ',', '.') }} đ
                                            </strong>
                                        </div>

                                        @if ($itemTotalFine > 0)
                                            <div class="small mt-1">
                                                @if ($item->fine_paid_at)
                                                    <span class="text-success">
                                                        Đã thanh toán
                                                    </span>
                                                @else
                                                    <span class="text-danger">
                                                        Chưa ghi nhận thanh toán
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">
                                            Chưa xác định
                                        </span>
                                    @endif
                                </td>

                                {{-- Ghi nhận tình trạng khi trả sách --}}
                                <td style="min-width: 230px;">
                                    @if (
                                        $loan->status === 'borrowed' &&
                                        $item->resolved_at === null &&
                                        $item->returned_at === null &&
                                        $item->book_copy_id !== null
                                    )
                                        <form
                                            action="{{ route('librarian.loans.items.return', [$loan, $item]) }}"
                                            method="POST"
                                            onsubmit="return confirm('Xác nhận xử lý bản sách này?')"
                                        >
                                            @csrf

                                            <div class="mb-2">
                                                <label class="form-label small">
                                                    Tình trạng sách
                                                </label>

                                                <select
                                                    name="condition"
                                                    class="form-select form-select-sm"
                                                    required
                                                >
                                                    <option value="normal">
                                                        Bình thường
                                                    </option>

                                                    <option value="damaged">
                                                        Bị hỏng
                                                    </option>

                                                    <option value="lost">
                                                        Bị mất
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label small">
                                                    Ghi chú
                                                </label>

                                                <textarea
                                                    name="note"
                                                    class="form-control form-control-sm"
                                                    rows="2"
                                                    maxlength="1000"
                                                    placeholder="Ghi chú tình trạng sách..."
                                                ></textarea>
                                            </div>

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-success w-100"
                                            >
                                                Xác nhận xử lý
                                            </button>
                                        </form>

                                    @elseif ($item->resolved_at !== null || $item->returned_at !== null)
                                        <span class="text-success small">
                                            Đã xử lý
                                        </span>

                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Phiếu chưa có sách.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Tổng tiền phạt của phiếu --}}
            @if ($loan->status !== 'pending' && $loan->status !== 'rejected')
                <div class="border rounded p-3 mt-4">
                    <h2 class="h5 mb-3">
                        Tổng hợp phí phát sinh
                    </h2>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Tiền phạt quá hạn:</span>
                        <strong>
                            {{ number_format($totalOverdueFine, 0, ',', '.') }} đ
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Tiền bồi thường hỏng/mất:</span>
                        <strong>
                            {{ number_format($totalCompensation, 0, ',', '.') }} đ
                        </strong>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <strong>Tổng phí đã ghi nhận:</strong>
                        <strong class="{{ $totalFine > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($totalFine, 0, ',', '.') }} đ
                        </strong>
                    </div>
                    @if ($itemTotalFine > 0)
                        <div class="mt-2">

                            @if ($item->fine_paid_at)
                                <span class="badge bg-success">
                                    Đã thanh toán
                                </span>

                                <div class="small text-muted mt-1">
                                    {{ $item->fine_paid_at->format('d/m/Y H:i') }}
                                </div>

                            @elseif ($item->resolved_at !== null)
                                <span class="badge bg-warning text-dark">
                                    Chưa thanh toán
                                </span>

                                <form
                                    method="POST"
                                    action="{{ route('librarian.loans.items.confirm-payment', [$loan, $item]) }}"
                                    class="mt-2"
                                    onsubmit="return confirm('Bạn xác nhận đã nhận đủ {{ number_format($itemTotalFine, 0, ',', '.') }} đ?')"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-success"
                                    >
                                        Xác nhận đã thu tiền
                                    </button>
                                </form>
                            @endif

                        </div>
                    @endif

                </div>
            @endif

            {{-- Duyệt hoặc từ chối phiếu đang chờ --}}
            @if ($loan->status === 'pending')
                <div class="d-flex flex-wrap gap-2 mt-4">

                    <form
                        id="issue-loan-form"
                        action="{{ route('librarian.loans.issue', $loan) }}"
                        method="POST"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Duyệt và giao sách
                        </button>
                    </form>

                    <form
                        action="{{ route('librarian.loans.reject', $loan) }}"
                        method="POST"
                        onsubmit="return confirm('Bạn chắc chắn muốn từ chối phiếu này?')"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="btn btn-outline-danger"
                        >
                            Từ chối yêu cầu
                        </button>
                    </form>

                </div>
            @endif

        </div>
    </div>
</div>
@endsection
