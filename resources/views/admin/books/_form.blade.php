@php
    $selectedAuthorIds = old(
        'author_ids',
        $book->exists ? $book->authors->modelKeys() : []
    );
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
    <label for="total_copies" class="form-label">Tổng số bản*</label>

    <input
        type="number"
        id="total_copies"
        name="total_copies"
        min="0"
        class="form-control @error('total_copies') is-invalid @enderror"
        value="{{ old('total_copies', $book->total_copies) }}"
    >

    <div id="total_copies-error" class="invalid-feedback">
        @error('total_copies')
            {{ $message }}
        @enderror
    </div>
</div>

{{-- Số bản còn lại --}}
<div class="mb-3">
    <label for="available_copies" class="form-label">
        Số bản còn lại*
    </label>

    <input
        type="number"
        id="available_copies"
        name="available_copies"
        min="0"
        class="form-control @error('available_copies') is-invalid @enderror"
        value="{{ old('available_copies', $book->available_copies) }}"
    >

    <div id="available_copies-error" class="invalid-feedback">
        @error('available_copies')
            {{ $message }}
        @enderror
    </div>
</div>

{{-- Tác giả --}}
<div class="mb-3">
    <label class="form-label">Tác giả*</label>

    <div class="dropdown">
        <button
            type="button"
            id="authorsDropdown"
            class="btn btn-outline-secondary dropdown-toggle w-100 text-start @if($errors->has('author_ids') || $errors->has('author_ids.*')) border-danger @endif"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
        >
            <span id="selectedAuthorsText">
                @if (count($selectedAuthorIds) > 0)
                    Đã chọn {{ count($selectedAuthorIds) }} tác giả
                @else
                    -- Chọn tác giả --
                @endif
            </span>
        </button>

        <div
            class="dropdown-menu w-100 p-3"
            aria-labelledby="authorsDropdown"
            style="max-height: 250px; overflow-y: auto;"
        >
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
    </div>

    <div id="author_ids-error" class="text-danger small mt-1">
        @error('author_ids')
            {{ $message }}
        @enderror

        @error('author_ids.*')
            {{ $message }}
        @enderror
    </div>
</div>

{{-- Ảnh bìa sách --}}
<div class="mb-3">
    <label for="cover" class="form-label">Ảnh bìa sách</label>

    <input
        type="file"
        id="cover"
        name="cover"
        accept="image/jpeg,image/png,image/webp"
        class="form-control @error('cover') is-invalid @enderror"
    >

    <div id="cover-error" class="invalid-feedback">
        @error('cover')
            {{ $message }}
        @enderror
    </div>
</div>

{{-- Real-time Validation --}}
<script>
    (() => {
        const form = document.getElementById('title')?.closest('form');

        if (!form) {
            return;
        }

        const timers = {};
        const controllers = {};


        const inputFields = [
            'isbn',
            'title',
            'description',
            'publisher',
            'published_year',
            'total_copies',
            'available_copies'
        ];


        function showError(field, message) {
            const input = field === 'author_ids'
                ? document.getElementById('authorsDropdown')
                : document.getElementById(field);

            const errorElement = document.getElementById(
                field + '-error'
            );

            if (!input || !errorElement) {
                return;
            }

            const cssClass = field === 'author_ids'
                ? 'border-danger'
                : 'is-invalid';

            input.classList.toggle(cssClass, Boolean(message));
            errorElement.textContent = message || '';
        }

        async function validateField(field) {
            const controller = new AbortController();
            controllers[field] = controller;

            // Lấy dữ liệu hiện tại từ toàn bộ form
            const data = new FormData(form);
            if (field !== 'cover') {
                data.delete('cover');
            }

            // Chỉ validate trường đang thay đổi
            const validateOnly = field === 'author_ids'
                ? 'author_ids,author_ids.*'
                : field;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Precognition': 'true',
                        'Precognition-Validate-Only': validateOnly
                    },
                    body: data,
                    signal: controller.signal
                });

                if (controller.signal.aborted) {
                    return;
                }

                if (response.status === 422) {
                    const result = await response.json();

                    if (controller.signal.aborted) {
                        return;
                    }

                    const errors = result.errors || {};

                    let message = errors[field]?.[0] || '';

                    if (field === 'author_ids' && !message) {
                        const key = Object.keys(errors).find(
                            key => key.startsWith('author_ids.')
                        );

                        if (key) {
                            message = errors[key][0];
                        }
                    }

                    showError(field, message);
                } else if (response.status === 204) {
                    showError(field, '');
                } else {
                    console.error(
                        'Không kiểm tra được ' + field,
                        response.status
                    );
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Lỗi Validation:', error);
                }
            }
        }

        // Đợi người dùng ngừng nhập trước khi gửi request
        function scheduleValidation(field) {
            clearTimeout(timers[field]);
            controllers[field]?.abort();

            timers[field] = setTimeout(() => {
                validateField(field);
            }, 400);
        }

        inputFields.forEach(field => {
            const input = document.getElementById(field);

            input.addEventListener('input', () => {
                scheduleValidation(field);

                if (field === 'total_copies') {
                    const available = document.getElementById(
                        'available_copies'
                    );

                    if (available.value !== '') {
                        scheduleValidation('available_copies');
                    }
                }
            });
        });

        // Thể loại
        document.getElementById('category_id')
            .addEventListener('change', () => {
                scheduleValidation('category_id');
            });
        //Ảnh bìa sách
        const coverInput = document.getElementById('cover');
        coverInput.addEventListener('change', () => {
            scheduleValidation('cover');
        });

        // Tác giả
        const authorCheckboxes = document.querySelectorAll(
            'input[name="author_ids[]"]'
        );

        const selectedAuthorsText = document.getElementById(
            'selectedAuthorsText'
        );

        function updateSelectedAuthors() {
            const count = document.querySelectorAll(
                'input[name="author_ids[]"]:checked'
            ).length;

            selectedAuthorsText.textContent = count > 0
                ? `Đã chọn ${count} tác giả`
                : '-- Chọn tác giả --';
        }

        authorCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateSelectedAuthors();
                scheduleValidation('author_ids');
            });
        });

        updateSelectedAuthors();
    })();
</script>
