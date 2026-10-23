<?php

namespace App\Http\Controllers\Member;

use App\Enums\BookCopyStatus;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\LoanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookSelectionController extends Controller
{
    // Tạo key Session riêng cho từng Member
    private function selectionKey(Request $request): string
    {
        $memberId = (int) $request->session()
            ->get('manual_auth_user_id');

        return 'loan_selected_books_'.$memberId;
    }

    // Hiển thị danh sách sách đã chọn
    public function index(Request $request): View
    {
        $key = $this->selectionKey($request);

        $selectedIds = $request->session()->get($key, []);

        $books = Book::query()
            ->with(['authors', 'category'])
            ->withCount([
                'copies as available_copy_count' => function (Builder $query) {
                    $query->where(
                        'status',
                        BookCopyStatus::Available->value
                    );
                },
            ])
            ->whereIn('id', $selectedIds)
            ->orderBy('title')
            ->get();

        // Loại bỏ ID sách đã bị xóa khỏi danh sách tạm
        $request->session()->put(
            $key,
            $books->pluck('id')->all()
        );

        return view('member.selection.index', compact('books'));
    }

    // Thêm một đầu sách vào danh sách mượn tạm
    public function store(Request $request, Book $book): RedirectResponse
    {
        $hasAvailableCopy = (int) $book->available_copies > 0
            && $book->copies()
                ->where('status', BookCopyStatus::Available->value)
                ->exists();

        if (! $hasAvailableCopy) {
            throw ValidationException::withMessages([
                'book_ids' => __('message.loans.books_unavailable'),
            ]);
        }

        $key = $this->selectionKey($request);

        $selectedIds = array_map(
            'intval',
            $request->session()->get($key, [])
        );

        // Chỉ chọn một bản cho mỗi đầu sách
        if (! in_array($book->id, $selectedIds, true)) {
            $selectedIds[] = $book->id;
        }

        $request->session()->put($key, $selectedIds);

        return redirect()
            ->route('member.selection.index')
            ->with('success', __('message.loans.selection_added'));
    }

    // Bỏ một đầu sách khỏi danh sách tạm
    public function destroy(
        Request $request,
        int $bookId
    ): RedirectResponse {
        $key = $this->selectionKey($request);

        $selectedIds = $request->session()->get($key, []);

        $selectedIds = array_values(array_filter(
            $selectedIds,
            fn ($id) => (int) $id !== $bookId
        ));

        $request->session()->put($key, $selectedIds);

        return redirect()
            ->route('member.selection.index')
            ->with('success', __('message.loans.selection_removed'));
    }

    // Xác nhận và tạo phiếu mượn chính thức
    public function submit(
        Request $request,
        LoanService $loanService
    ): RedirectResponse {
        $key = $this->selectionKey($request);

        $bookIds = array_map(
            'intval',
            $request->session()->get($key, [])
        );

        if (empty($bookIds)) {
            throw ValidationException::withMessages([
                'book_ids' => __('message.loans.selection_empty'),
            ]);
        }

        $memberId = (int) $request->session()
            ->get('manual_auth_user_id');

        // Service kiểm tra lại và tạo Loan và LoanItem
        $loanService->requestLoan($memberId, $bookIds);

        // Chỉ xóa Session sau khi tạo phiếu thành công
        $request->session()->forget($key);

        return redirect()
            ->route('member.loans.index')
            ->with('success', __('message.loans.request_created'));
    }
}
