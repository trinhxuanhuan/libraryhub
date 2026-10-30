@php
    $selectedAuthorIds = session()->hasOldInput()
        ? old('author_ids', [])
        : ($book->exists ? $book->authors->modelKeys() : []);
@endphp

{{-- ISBN --}}
<div class="mb-3">
    <label for="isbn" class="form-label">ISBN*</label>

    <input
        type="text"
        id="isbn"
        name="isbn"
        class="form-control @error('isbn') is-invalid @enderror"
        value="{{ old('isbn', $book->isbn) }}"
    >

    <div id="isbn-error" class="invalid-feedback">
        @error('isbn')
            {{ $message }}
        @enderror
    </div>
</div>

{{-- Tên sách --}}
<div class="mb-3">
    <label for="title" class="form-label">Tên sách*</label>

    <input
        type="text"
        id="title"
        name="title"
        class="form-control @error('title') is-invalid @enderror"
        value="{{ old('title', $book->title) }}"
    >

    <div id="title-error" class="invalid-feedback">
        @error('title')
            {{ $message }}
        @enderror
    </div>
</div>

{{-- Thể loại --}}
<div class="mb-3">
    <label for="category_id" class="form-label">Thể loại*</label>

    <select
        name="category_id"
        id="category_id"
        class="form-select @error('category_id') is-invalid @enderror"
    >
        <option value="">-- Chọn thể loại --</option>

        @foreach ($categories as $category)
            <option
                value="{{ $category->id }}"
                @selected(old('category_id', $book->category_id) == $category->id)
            >
                {{ $category->name }}
            </option>
        @endforeach
    </select>

    <div id="category_id-error" class="invalid-feedback">
        @error('category_id')
            {{ $message }}
        @enderror
    </div>
</div>

{{-- Mô tả --}}
<div class="mb-3">
    <label for="description" class="form-label">Mô tả</label>

    <textarea
        id="description"
        name="description"
        class="form-control @error('description') is-invalid @enderror"
        rows="3"
    >{{ old('description', $book->description) }}</textarea>

    <div id="description-error" class="invalid-feedback">
        @error('description')
            {{ $message }}
        @enderror
    </div>
</div>

{{-- Nhà xuất bản --}}
<div class="mb-3">
    <label for="publisher" class="form-label">Nhà xuất bản</label>

    <input
        type="text"
        id="publisher"
        name="publisher"
        class="form-control @error('publisher') is-invalid @enderror"
        value="{{ old('publisher', $book->publisher) }}"
    >

    <div id="publisher-error" class="invalid-feedback">
        @error('publisher')
            {{ $message }}
        @enderror
    </div>
</div>

{{-- Năm xuất bản --}}
<div class="mb-3">
    <label for="published_year" class="form-label">Năm xuất bản*</label>

    <input
        type="number"
        id="published_year"
        name="published_year"
        class="form-control @error('published_year') is-invalid @enderror"
        value="{{ old('published_year', $book->published_year) }}"
    >

    <div id="published_year-error" class="invalid-feedback">
        @error('published_year')
            {{ $message }}
        @enderror
    </div>
</div>


{{-- Tổng số bản --}}
<div class="mb-3">
    <label for="total_copies" class="form-label">
        Tổng số bản*
    </label>

    <input
        type="number"
        id="total_copies"
        name="total_copies"
        min="{{ $book->exists ? $book->total_copies : 0 }}"
        class="form-control @error('total_copies') is-invalid @enderror"
        value="{{ old('total_copies', $book->total_copies) }}"
        required
    >

    @error('total_copies')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
    @enderror

    @if ($book->exists)
        <div class="form-text">
            Chỉ được tăng hoặc giữ nguyên tổng số bản sách.
        </div>
    @endif
</div>

{{-- Số bản khả dụng --}}
<div class="mb-3">
    <label for="available_copies" class="form-label">
        Số bản khả dụng
    </label>

    @if ($book->exists)
        {{-- Edit: Chỉ hiển thị --}}
        <input
            type="number"
            id="available_copies"
            class="form-control"
            value="{{ $book->available_copies }}"
            readonly
        >

        <div class="form-text">
            Số lượng được quản lý theo trạng thái
            từng bản sách, không chỉnh sửa trực tiếp.
        </div>
    @else
        {{-- Create: Nhập số bản khả dụng ban đầu --}}
        <input
            type="number"
            id="available_copies"
            name="available_copies"
            min="0"
            class="form-control @error('available_copies') is-invalid @enderror"
            value="{{ old('available_copies', $book->available_copies) }}"
            required
        >

        @error('available_copies')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
    @endif
</div>


{{-- Tác giả --}}
<div class="mb-3">
    <label for="author_ids" class="form-label">
        Tác giả*
    </label>

    <select
        id="author_ids"
        name="author_ids[]"
        class="form-select"
        data-searchable-select
        data-placeholder="Tìm kiếm và chọn tác giả..."
        multiple
    >
        @foreach ($authors as $author)
            <option
                value="{{ $author->id }}"
                @selected(in_array($author->id, $selectedAuthorIds))
            >
                {{ $author->name }}
            </option>
        @endforeach
    </select>

    @error('author_ids')
        <div class="text-danger small mt-1">
            {{ $message }}
        </div>
    @enderror

    @error('author_ids.*')
        <div class="text-danger small mt-1">
            {{ $message }}
        </div>
    @enderror
</div>
    <div class="mb-3">
        <label for="cover" class="form-label">
            Ảnh bìa sách
        </label>

        <input
            type="file"
            id="cover"
            name="cover"
            accept=".jpg,.jpeg,.png,.webp"
            class="form-control @error('cover') is-invalid @enderror"
        >

        @error('cover')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror

        @if ($book->cover_path)
            <div class="mt-3">
                <p class="form-text mb-2">Ảnh bìa hiện tại:</p>

                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($book->cover_path) }}"
                    alt="Bìa sách {{ $book->title }}"
                    class="img-thumbnail"
                    style="max-width:150px;max-height:180px;object-fit:contain;"
                >
            </div>
        @endif
    </div>
</div>
