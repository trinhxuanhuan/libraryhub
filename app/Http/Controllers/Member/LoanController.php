<?php

namespace App\Http\Controllers\Member;

use App\Enums\BookCopyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoanRequest;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(Request $request): View
    {
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

        // Tìm theo tên sách, ISBN hoặc tác giả
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

        // Lọc theo thể loại
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Sắp xếp
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

        // Mỗi trang 12 đầu sách
        $books = $query->paginate(12)->withQueryString();

        $categories = Category::query()
            ->orderBy('name')
            ->get();

        return view('member.books.index', compact('books', 'categories'));
    }

    // Xử lý yêu cầu mượn sách
    public function store(StoreLoanRequest $request, LoanService $loanService): RedirectResponse
    {
        $bookIds = array_map('intval', $request->validated('book_ids'));
        $memberId = (int) $request->session()
            ->get('manual_auth_user_id');

        $loanService->requestLoan($memberId, $bookIds);

        return redirect()
            ->route('member.loans.index')
            ->with('success', __('message.loans.request_created'));
    }

    // Xem các phiếu mượn của Member đang đăng nhập
    public function myLoans(Request $request): View
    {
        $memberId = (int) $request->session()
            ->get('manual_auth_user_id');

        $loans = Loan::query()
            ->with('items.book')
            ->where('user_id', $memberId)
            ->orderByDesc('id')
            ->get();

        return view('member.loans.index', compact('loans'));
    }

    public function show(Book $book): View
    {
        $book->load(['category', 'authors']);

        $book->loadCount([
            'copies as available_copy_count' => function (Builder $query) {
                $query->where(
                    'status',
                    BookCopyStatus::Available->value
                );
            },
        ]);

        return view('member.books.show', compact('book'));
    }

    public function renew(Request $request, Loan $loan, LoanService $loanService): RedirectResponse
    {
        $memberId = (int) $request->session()
            ->get('manual_auth_user_id');

        $loanService->renewLoan($loan->id, $memberId);

        return redirect()
            ->route('member.loans.index')
            ->with('success', 'Gia hạn phiếu mượn thành công.');
    }
}
