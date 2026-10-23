<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BookCopyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $search = trim($filters['search'] ?? '');
        $categoryId = $filters['category_id'] ?? null;

        $query = Book::with(['category', 'authors'])
            ->orderBy('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $books = $query->paginate(10)->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('admin.books.index', [
            'books' => $books,
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('admin.books.create', [
            'book' => new Book,
            'categories' => Category::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
        ]);
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $authorIds = $data['author_ids'];

        // File upload không được đưa trực tiếp vào Book::create()
        unset($data['author_ids'], $data['cover']);

        $newCoverPath = null;

        try {
            // Upload ảnh bìa nếu Admin đã chọn file
            if ($request->hasFile('cover')) {
                $newCoverPath = $request->file('cover')
                    ->store('book-covers', 'public');

                if ($newCoverPath === false) {
                    throw new \RuntimeException('Unable to store book cover.');
                }

                // Lưu đường dẫn ảnh vào bảng books
                $data['cover_path'] = $newCoverPath;
            }

            DB::transaction(function () use ($data, $authorIds) {
                // Tạo đầu sách
                $book = Book::create($data);

                // Liên kết tác giả
                $book->authors()->sync($authorIds);

                // Tạo từng bản sách vật lý
                for ($i = 1; $i <= $book->total_copies; $i++) {
                    $book->copies()->create([
                        'barcode' => sprintf(
                            'COPY-%06d-%04d',
                            $book->id,
                            $i
                        ),
                        'status' => $i <= $book->available_copies
                            ? BookCopyStatus::Available
                            : BookCopyStatus::Unverified,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // Nếu Transaction lỗi, xóa ảnh vừa upload
            if ($newCoverPath !== null && $newCoverPath !== false) {
                Storage::disk('public')->delete($newCoverPath);
            }

            throw $e;
        }

        return redirect()
            ->route('admin.books.index')
            ->with('success', __('message.books.created'));
    }

    public function edit(Book $book): View
    {
        $book->load('authors');

        return view('admin.books.edit', [
            'book' => $book,
            'categories' => Category::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
        ]);
    }

    public function update(
        UpdateBookRequest $request,
        Book $book
    ): RedirectResponse {
        $data = $request->validated();

        $authorIds = $data['author_ids'];
        unset($data['author_ids'], $data['cover']);

        $newCoverPath = null;
        $oldCoverPath = null;

        try {
            // Upload ảnh mới nếu Admin có chọn
            if ($request->hasFile('cover')) {
                $newCoverPath = $request->file('cover')
                    ->store('book-covers', 'public');

                if ($newCoverPath === false) {
                    throw new \RuntimeException('Unable to store book cover.');
                }

                $data['cover_path'] = $newCoverPath;
            }

            DB::transaction(function () use (
                $book,
                $data,
                $authorIds,
                &$oldCoverPath
            ) {
                // Khóa đầu sách trong lúc cập nhật
                $book = Book::query()
                    ->lockForUpdate()
                    ->findOrFail($book->id);

                // Lưu đường dẫn ảnh cũ để xóa sau khi cập nhật thành công
                $oldCoverPath = $book->cover_path;

                // Lấy số lượng từ book_copies
                $currentTotal = $book->copies()->count();

                $currentAvailable = $book->copies()
                    ->where('status', BookCopyStatus::Available->value)
                    ->count();

                // Kiểm tra dữ liệu giữa hai bảng
                if (
                    $currentTotal !== (int) $book->total_copies ||
                    $currentAvailable !== (int) $book->available_copies
                ) {
                    throw ValidationException::withMessages([
                        'total_copies' => __('message.books.quantity_out_of_sync'),
                    ]);
                }

                $newTotal = (int) $data['total_copies'];

                // Chưa cho giảm bản sách
                if ($newTotal < $currentTotal) {
                    throw ValidationException::withMessages([
                        'total_copies' => __('message.books.decrease_copies_not_supported'),
                    ]);
                }

                $addedCopies = $newTotal - $currentTotal;

                // Cập nhật đầu sách
                $book->update([
                    ...$data,
                    'available_copies' => $currentAvailable + $addedCopies,
                ]);

                // Đồng bộ tác giả
                $book->authors()->sync($authorIds);

                // Tạo thêm bản vật lý nếu tổng số tăng
                for ($i = $currentTotal + 1; $i <= $newTotal; $i++) {
                    $book->copies()->create([
                        'barcode' => sprintf(
                            'COPY-%06d-%04d',
                            $book->id,
                            $i
                        ),
                        'status' => BookCopyStatus::Available,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // Cập nhật thất bại thì xóa ảnh mới upload
            if ($newCoverPath !== null && $newCoverPath !== false) {
                Storage::disk('public')->delete($newCoverPath);
            }

            throw $e;
        }

        // Chỉ xóa ảnh cũ sau khi Transaction thành công
        if ($newCoverPath !== null && $oldCoverPath) {
            Storage::disk('public')->delete($oldCoverPath);
        }

        return redirect()
            ->route('admin.books.index')
            ->with('success', __('message.books.updated'));
    }

    public function destroy(Book $book): RedirectResponse
    {
        $book->delete();

        return redirect()
            ->route('admin.books.index')
            ->with('success', __('message.books.deleted'));
    }
}
