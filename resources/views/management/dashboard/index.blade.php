
@extends('layouts.app')

@section('title', 'Dashboard — LibraryHub')

@section('content')
<div class="dashboard-page">

    <div class="mb-4">
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">
            Tổng quan hoạt động thư viện và thống kê mượn sách.
        </p>
    </div>

    @php
        $cards = [
            [
                'label' => 'Tổng đầu sách',
                'value' => $statistics['books'],
                'color' => 'border-primary',
            ],
            [
                'label' => 'Thành viên',
                'value' => $statistics['members'],
                'color' => 'border-success',
            ],
            [
                'label' => 'Phiếu đang mượn',
                'value' => $statistics['borrowed'],
                'color' => 'border-warning',
            ],
            [
                'label' => 'Phiếu quá hạn',
                'value' => $statistics['overdue'],
                'color' => 'border-danger',
            ],
        ];

        $maxMonthlyCount = max(
            1,
            (int) $monthlyBorrowings->max('count')
        );
    @endphp

    {{-- Bốn thẻ thống kê --}}
    <div class="row g-3 mb-4">
        @foreach ($cards as $card)
            <div class="col-sm-6 col-xl-3">
                <div
                    class="card shadow-sm h-100 border-start border-4 {{ $card['color'] }}"
                >
                    <div class="card-body">
                        <div class="text-muted mb-2">
                            {{ $card['label'] }}
                        </div>

                        <div class="fs-2 fw-bold">
                            {{ number_format($card['value']) }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">

        {{-- Biểu đồ lượt mượn sáu tháng --}}
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">
                        Lượt mượn 6 tháng gần nhất
                    </h2>
                </div>

                <div class="card-body">
                    <p class="text-muted small">
                        Số phiếu đã được giao sách theo từng tháng.
                    </p>

                    <div
                        class="row g-2 align-items-end"
                        style="height: 230px;"
                    >
                        @foreach ($monthlyBorrowings as $month)
                            <div
                                class="col-2 h-100 d-flex flex-column align-items-center justify-content-end"
                            >
                                <div class="small fw-semibold mb-1">
                                    {{ $month['count'] }}
                                </div>

                                @if ($month['count'] > 0)
                                    <div
                                        class="bg-primary rounded-top"
                                        style="
                                            width: 65%;
                                            height: {{ max(8, (int) round($month['count'] / $maxMonthlyCount * 150)) }}px;
                                        "
                                        title="{{ $month['count'] }} lượt mượn"
                                    ></div>
                                @endif

                                <div class="small text-muted mt-2">
                                    {{ $month['label'] }}
                                </div>

                                <div class="small text-muted">
                                    {{ $month['year'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Top 5 sách --}}
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">
                        Top 5 sách được mượn nhiều nhất
                    </h2>
                </div>

                <div class="card-body p-0">
                    <div class="list-group list-group-flush">

                        @forelse ($topBooks as $index => $book)
                            <div
                                class="list-group-item d-flex justify-content-between align-items-center gap-3"
                            >
                                <div>
                                    <span class="text-muted me-2">
                                        {{ $index + 1 }}.
                                    </span>

                                    <span class="fw-semibold">
                                        {{ $book->title }}
                                    </span>
                                </div>

                                <span class="badge bg-primary rounded-pill">
                                    {{ number_format($book->borrow_count) }}
                                </span>
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">
                                Chưa có lượt mượn sách nào.
                            </div>
                        @endforelse

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
