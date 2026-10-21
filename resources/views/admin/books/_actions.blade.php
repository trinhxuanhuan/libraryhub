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

        <button type="submit" class="btn btn-danger btn-sm">
            Xóa
        </button>
    </form>
</div>
