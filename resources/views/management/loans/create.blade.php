
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

                    <select
                        name="member_id"
                        id="member_id"
                        class="form-select"
                        data-searchable-select
                        data-placeholder="Tìm kiếm thành viên..."
                        required
                    >
                        <option value="">-- Chọn thành viên --</option>

                        @foreach ($members as $member)
                            <option value="{{ $member->id }}"
                                @selected(old('member_id') == $member->id)>
                                {{ $member->name }} ({{ $member->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Chọn đầu sách --}}
                <div class="mb-4">
                    <label for="book_ids" class="form-label fw-semibold">
                        Chọn đầu sách
                    </label>

                    <p class="text-muted small">
                        Có thể tìm kiếm và chọn nhiều sách trong cùng một phiếu.
                    </p>

                    <select
                        name="book_ids[]"
                        id="book_ids"
                        class="form-select"
                        data-searchable-select
                        data-placeholder="Tìm kiếm và chọn đầu sách..."
                        multiple
                    >
                        @foreach ($books as $book)
                            <option
                                value="{{ $book->id }}"
                                @selected(in_array(
                                    $book->id,
                                    array_map('intval', old('book_ids', [])),
                                    true
                                ))
                            >
                                {{ $book->title }} (Còn {{ $book->available_copies }} bản)
                            </option>
                        @endforeach
                    </select>

                    @error('book_ids')
                        <div class="text-danger small mt-1">
                            {{ $message }}
                        </div>
                    @enderror

                    @error('book_ids.*')
                        <div class="text-danger small mt-1">
                            {{ $message }}
                        </div>
                    @enderror
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
