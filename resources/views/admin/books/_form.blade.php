@php
    $selectedAuthorIds = old(
        'author_ids',
        $book->exists ? $book->authors->modelKeys() : []
    );
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-3">
    <label for="isbn" class="form-label">ISBN*</label>
    <input
        type="text"
        id="isbn"
        name="isbn"
        class="form-control"
        value="{{ old('isbn', $book->isbn) }}"
    >
</div>

<div class="mb-3">
    <label for="title" class="form-label">Tên sách*</label>
    <input
        type="text"
        id="title"
        name="title"
        class="form-control"
        value="{{ old('title', $book->title) }}"
    >
</div>

<div class="mb-3">
    <label for="category_id" class="form-label">Thể loại*</label>

    <select
        name="category_id"
        id="category_id"
        class="form-select"
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
</div>

<div class="mb-3">
    <label for="description" class="form-label">Mô tả</label>
    <textarea
        id="description"
        name="description"
        class="form-control"
        rows="3"
    >{{ old('description', $book->description) }}</textarea>
</div>

<div class="mb-3">
    <label for="publisher" class="form-label">Nhà xuất bản*</label>
    <input
        type="text"
        id="publisher"
        name="publisher"
        class="form-control"
        value="{{ old('publisher', $book->publisher) }}"
    >
</div>

<div class="mb-3">
    <label for="published_year" class="form-label">Năm xuất bản*</label>
    <input
        type="number"
        id="published_year"
        name="published_year"
        class="form-control"
        value="{{ old('published_year', $book->published_year) }}"
    >
</div>

<div class="mb-3">
    <label for="total_copies" class="form-label">Tổng số bản*</label>
    <input
        type="number"
        id="total_copies"
        name="total_copies"
        min="0"
        class="form-control"
        value="{{ old('total_copies', $book->total_copies) }}"
    >
</div>

<div class="mb-3">
    <label for="available_copies" class="form-label">Số bản còn lại*</label>
    <input
        type="number"
        id="available_copies"
        name="available_copies"
        min="0"
        class="form-control"
        value="{{ old('available_copies', $book->available_copies) }}"
    >
</div>

<div class="mb-3">
    <label class="form-label">Tác giả*</label>

    @foreach ($authors as $author)
        <div class="form-check">
            <input
                type="checkbox"
                name="author_ids[]"
                value="{{ $author->id }}"
                id="author_{{ $author->id }}"
                class="form-check-input"
                @checked(in_array($author->id, $selectedAuthorIds))
            >

            <label
                for="author_{{ $author->id }}"
                class="form-check-label"
            >
                {{ $author->name }}
            </label>
        </div>
    @endforeach
</div>
