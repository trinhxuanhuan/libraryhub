<div class="d-flex gap-2">
    <a
        href="{{ route('admin.books.edit', $book) }}"
        class="btn btn-warning btn-sm"
    >
        Sửa
    </a>

    <form
        action="{{ route('admin.books.destroy', $book) }}"
        method="POST"
        onsubmit="return confirm('Bạn có chắc chắn muốn xóa sách này không?');"
    >
        @csrf
        @method('DELETE')

        <a
            href="{{ route('admin.books.retire', $book) }}"
            class="btn btn-outline-danger btn-sm"
        >
            Ngừng cung cấp
        </a>
    </form>
</div>
