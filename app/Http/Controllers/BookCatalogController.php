<?php

namespace App\Http\Controllers;

use App\Enums\BookCopyStatus;
use App\Models\Book;
use App\Models\Category;
use App\Services\ManualAuthenticator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookCatalogController extends Controller
{
    // Hiển thị danh mục sách công khai
    public function index(
        Request $request,
        ManualAuthenticator $authenticator
    ): View {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort' => ['nullable', 'in:latest,title_asc,title_desc'],
        ]);

        $query = Book::query()
            ->with(['category', 'authors'])
            ->withCount([
                'copies as available_copy_count' => function (Builder $query) {
                    $query->where(
                        'status',
                        BookCopyStatus::Available->value
                    );
                },
            ]);

        // Tìm kiếm theo tên sách, ISBN hoặc tác giả
        $keyword = trim($filters['q'] ?? '');

        if ($keyword !== '') {
            $query->where(function (Builder $query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('isbn', 'like', "%{$keyword}%")
                    ->orWhereHas('authors', function (Builder $authorQuery) use ($keyword) {
                        $authorQuery->where('name', 'like', "%{$keyword}%");
                    });
            });
        }

        // Lọc sách theo thể loại
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Sắp xếp danh sách sách
        switch ($filters['sort'] ?? 'latest') {
            case 'title_asc':
                $query->orderBy('title')->orderBy('id');
                break;

            case 'title_desc':
                $query->orderByDesc('title')->orderByDesc('id');
                break;

            default:
                $query->orderByDesc('id');
                break;
        }

        // Phân trang: 12 đầu sách mỗi trang
        $books = $query->paginate(12)->withQueryString();

        // Danh sách thể loại cho bộ lọc
        $categories = Category::query()
            ->orderBy('name')
            ->get();

        // Xác định người đang xem: Guest, Member, Admin hoặc Librarian
        $viewer = $authenticator->user($request);

        return view('member.books.index', compact(
            'books',
            'categories',
            'viewer'
        ));
    }

    // Hiển thị chi tiết một đầu sách
    public function show(
        Book $book,
        Request $request,
        ManualAuthenticator $authenticator
    ): View {
        // Lấy thông tin thể loại và tác giả
        $book->load(['category', 'authors']);

        // Đếm số bản vật lý đang khả dụng
        $book->loadCount([
            'copies as available_copy_count' => function (Builder $query) {
                $query->where(
                    'status',
                    BookCopyStatus::Available->value
                );
            },
        ]);

        // Xác định người đang xem để hiển thị nút mượn phù hợp
        $viewer = $authenticator->user($request);

        return view('member.books.show', compact(
            'book',
            'viewer'
        ));
    }
}
