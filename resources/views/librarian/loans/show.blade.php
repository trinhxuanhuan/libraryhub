
@extends('layouts.app')

@section('title', 'Chi tiết phiếu mượn — LibraryHub')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">
                Phiếu mượn #{{ $loan->id }}
            </h1>

            <a href="{{ route('librarian.loans.index') }}"
               class="btn btn-outline-secondary">
                Quay lại
            </a>
        </div>

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

        {{-- Thông tin phiếu --}}
        <div class="mb-4">
            <p>
                <strong>Người mượn:</strong>
                {{ $loan->user?->name ?? 'Không xác định' }}
            </p>

            <p>
                <strong>Ngày yêu cầu:</strong>
                {{ $loan->created_at?->format('d/m/Y H:i') }}
            </p>

            <p>
                <strong>Trạng thái:</strong>

                @switch($loan->status)
                    @case('pending')
                        <span class="badge bg-warning text-dark">
                            Chờ xử lý
                        </span>
                        @break

                    @case('borrowed')
                        <span class="badge bg-primary">
                            Đang mượn
                        </span>
                        @break

                    @case('returned')
                        <span class="badge bg-success">
                            Đã trả
                        </span>
                        @break

                    @case('rejected')
                        <span class="badge bg-danger">
                            Đã từ chối
                        </span>
                        @break
                @endswitch
            </p>

            @if ($loan->borrowed_at)
                <p>
                    <strong>Ngày giao:</strong>
                    {{ $loan->borrowed_at->format('d/m/Y H:i') }}
                </p>
            @endif

            @if ($loan->due_at)
                <p>
                    <strong>Hạn trả:</strong>
                    {{ $loan->due_at->format('d/m/Y') }}
                </p>
            @endif
        </div>

        <h2 class="h5 mb-3">Danh sách sách trong phiếu</h2>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Đầu sách</th>
                        <th>Bản sách / Barcode</th>
                        <th>Trạng thái trả</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($loan->items as $item)
                        <tr>
                            <td>
                                {{ $item->book?->title ?? 'Sách không tồn tại' }}
                            </td>

                            <td>
                                @if ($loan->status === 'pending')
                                    @php
                                        $copies = $availableCopiesByBook
                                            ->get($item->book_id, collect());
                                    @endphp

                                    <select
                                        name="copies[{{ $item->id }}]"
                                        form="issue-loan-form"
                                        class="form-select"
                                        required
                                    >
                                        <option value="">
                                            -- Chọn bản sách --
                                        </option>

                                        @foreach ($copies as $copy)
                                            <option
                                                value="{{ $copy->id }}"
                                                @selected(
                                                    old('copies.'.$item->id) == $copy->id
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

                            <td>
                                @if ($item->returned_at)
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

                            <td>
                                @if (
                                    $loan->status === 'borrowed' &&
                                    $item->returned_at === null
                                )
                                    <form
                                        action="{{ route('librarian.loans.items.return', [$loan, $item]) }}"
                                        method="POST"
                                        onsubmit="return confirm('Xác nhận đã nhận lại bản sách này?')"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-success"
                                        >
                                            Xác nhận trả
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Thao tác khi phiếu đang chờ xử lý --}}
        @if ($loan->status === 'pending')
            <div class="d-flex gap-2 mt-3">

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
@endsection
