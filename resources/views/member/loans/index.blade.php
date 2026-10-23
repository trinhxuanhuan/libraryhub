@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2>Phiếu mượn của tôi</h2>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <a href="{{ route('member.books.index') }}"
       class="btn btn-primary mb-3">
        Tiếp tục chọn sách
    </a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Mã phiếu</th>
                <th>Ngày yêu cầu</th>
                <th>Danh sách sách</th>
                <th>Trạng thái</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($loans as $loan)
                <tr>
                    <td>#{{ $loan->id }}</td>

                    <td>
                        {{ $loan->created_at->format('d/m/Y H:i') }}
                    </td>

                    <td>
                        <ul class="mb-0">
                            @foreach ($loan->items as $item)
                                <li>
                                    {{ $item->book?->title ?? 'Đầu sách không còn hiển thị' }}
                                </li>
                            @endforeach
                        </ul>
                    </td>

                    <td>
                        @if ($loan->status === 'pending')
                            <span class="badge bg-warning text-dark">
                                Chờ duyệt
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
                            <span class="badge bg-danger">
                                Từ chối
                            </span>
                        @else
                            {{ $loan->status }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        Bạn chưa có phiếu mượn nào.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
