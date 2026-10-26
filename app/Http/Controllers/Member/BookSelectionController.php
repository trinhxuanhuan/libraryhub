<?php

namespace App\Http\Controllers\Member;

use App\Enums\BookCopyStatus;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookSelectionController extends Controller
{
    // Tạo key Session riêng cho từng Member
    private function currentMember(Request $request): User
    {
        $memberId = (int) $request->session()
            ->get('manual_auth_user_id');

        return User::findOrFail($memberId);
    }

    // Hiển thị danh sách sách đã chọn
    public function index(Request $request): View
    {
        $books = $this->currentMember($request)
            ->selectedBooks()
            ->with(['authors', 'category'])
            ->withCount([
                'copies as available_copy_count' => function (Builder $query) {
                    $query->where(
                        'status',
                        BookCopyStatus::Available->value
                    );
                },
            ])
            ->orderBy('books.title')
            ->get();

        return view('member.selection.index', compact('books'));
    }

    // Thêm một đầu sách vào danh sách mượn tạm
    public function store(Request $request, Book $book): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);

        // Số bản thực tế có thể mượn
        $available = max(0, min(
            (int) $book->available_copies,
            (int) $book->copies()
                ->where('status', BookCopyStatus::Available->value)
                ->count()
        ));

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => __('message.loans.books_unavailable'),
            ]);
        }

        $user = $this->currentMember($request);

        // Thêm sách mới hoặc cập nhật số lượng nếu đã chọn
        $user->selectedBooks()->syncWithoutDetaching([
            $book->id => ['quantity' => $quantity],
        ]);

        return redirect()
            ->route('member.selection.index')
            ->with('success', __('message.loans.selection_added'));
    }

    // Bỏ một đầu sách khỏi danh sách tạm
    public function destroy(
        Request $request,
        int $bookId
    ): RedirectResponse {
        $user = $this->currentMember($request);

        $user->selectedBooks()->detach($bookId);

        return redirect()
            ->route('member.selection.index')
            ->with('success', __('message.loans.selection_removed'));
    }

    // Xác nhận và tạo phiếu mượn chính thức
    public function submit(
        Request $request,
        LoanService $loanService
    ): RedirectResponse {
        $user = $this->currentMember($request);

        $selections = $user->selectedBooks()
            ->get(['books.id']);

        $bookIds = $selections
            ->flatMap(fn (Book $book) => array_fill(
                0,
                (int) $book->pivot->quantity,
                (int) $book->id
            ))
            ->all();

        if (empty($bookIds)) {
            throw ValidationException::withMessages([
                'book_ids' => __('message.loans.selection_empty'),
            ]);
        }

        DB::transaction(function () use (
            $loanService,
            $user,
            $bookIds,
            $selections
        ) {
            $loanService->requestLoan($user->id, $bookIds);

            $user->selectedBooks()->detach(
                $selections->modelKeys()
            );
        });

        return redirect()
            ->route('member.loans.index')
            ->with('success', __('message.loans.request_created'));
    }
}
