<?php

namespace App\Http\Controllers\Member;

use App\Enums\BookCopyStatus;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\User;
use App\Services\BookSelectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function store(
        Request $request,
        Book $book,
        BookSelectionService $selectionService
    ): RedirectResponse {
        $validated = $request->validate([
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);

        $selectionService->add(
            $this->currentMember($request),
            $book,
            $quantity
        );

        return redirect()
            ->route('member.selection.index')
            ->with('success', __('message.loans.selection_added'));
    }

    // Bỏ một đầu sách khỏi danh sách tạm
    public function destroy(
        Request $request,
        int $bookId,
        BookSelectionService $selectionService
    ): RedirectResponse {
        $selectionService->remove(
            $this->currentMember($request),
            $bookId
        );

        return redirect()
            ->route('member.selection.index')
            ->with('success', __('message.loans.selection_removed'));
    }

    // Xác nhận và tạo phiếu mượn chính thức
    public function submit(
        Request $request,
        BookSelectionService $selectionService
    ): RedirectResponse {
        $selectionService->submit(
            $this->currentMember($request)
        );

        return redirect()
            ->route('member.loans.index')
            ->with('success', __('message.loans.request_created'));
    }
}
