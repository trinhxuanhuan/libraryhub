
@extends('layouts.app')

@section('title', 'Tạo phiếu mượn — LibraryHub')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Tạo phiếu mượn</h1>

                <a href="{{ route('management.loans.index') }}"
                   class="btn btn-outline-secondary">
                    Quay lại
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('management.loans.store') }}">
                @csrf

                <div class="mb-4">
                    <label for="member_id" class="form-label fw-semibold">
                        Thành viên mượn sách
                    </label>

                    <select name="member_id"
                            id="member_id"
                            class="form-select"
                            required>
                        <option value="">-- Chọn thành viên --</option>

                        @foreach ($members as $member)
                            <option value="{{ $member->id }}"
                                @selected(old('member_id') == $member->id)>
                                {{ $member->name }} ({{ $member->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <h2 class="h5">Chọn đầu sách</h2>
                    <p class="text-muted small">
                        Có thể chọn nhiều sách trong cùng một phiếu.
                    </p>

                    <div class="border rounded p-3"
                         style="max-height: 350px; overflow-y: auto;">

                        @forelse ($books as $book)
                            <div class="form-check mb-3">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="book_ids[]"
                                    value="{{ $book->id }}"
                                    id="book-{{ $book->id }}"
                                    @checked(in_array(
                                        $book->id,
                                        array_map('intval', old('book_ids', [])),
                                        true
                                    ))
                                >

                                <label class="form-check-label"
                                       for="book-{{ $book->id }}">
                                    <strong>{{ $book->title }}</strong>

                                    <span class="text-muted small">
                                        (Còn {{ $book->available_copies }} bản)
                                    </span>
                                </label>
                            </div>
                        @empty
                            <p class="text-muted mb-0">
                                Không có sách khả dụng.
                            </p>
                        @endforelse
                    </div>
                </div>

                <div class="alert alert-info">
                    Phiếu được tạo ở trạng thái
                    <strong>Chờ xử lý</strong>.
                    Bạn sẽ chọn bản sách vật lý và giao sách
                    ở màn hình chi tiết tiếp theo.
                </div>

                <button type="submit" class="btn btn-primary">
                    Tạo phiếu mượn
                </button>
            </form>

        </div>
    </div>
</div>
@endsection
