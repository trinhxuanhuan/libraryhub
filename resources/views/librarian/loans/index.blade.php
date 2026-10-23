@extends('layouts.app')

@section('title', 'Quản lý phiếu mượn — LibraryHub')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <h1 class="h3 mb-3">Quản lý phiếu mượn</h1>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Người mượn</th>
                        <th>Số đầu sách</th>
                        <th>Ngày yêu cầu</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($loans as $loan)
                        <tr>
                            <td>#{{ $loan->id }}</td>

                            <td>
                                {{ $loan->user?->name ?? 'Không xác định' }}
                            </td>

                            <td>{{ $loan->items_count }}</td>

                            <td>
                                {{ $loan->created_at?->format('d/m/Y H:i') }}
                            </td>

                            <td>
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
                                            Từ chối
                                        </span>
                                        @break

                                    @default
                                        <span class="badge bg-secondary">
                                            {{ $loan->status }}
                                        </span>
                                @endswitch
                            </td>
                            <td>
                                <a href="{{ route('librarian.loans.show', $loan) }}"
                                class="btn btn-sm btn-outline-primary">
                                    Xem / Xử lý
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                Chưa có phiếu mượn nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $loans->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
