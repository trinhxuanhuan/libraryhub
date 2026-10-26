<?php

namespace App\Services;

use App\Enums\BookCopyStatus;
use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookSelectionService
{
    public function __construct(
        private readonly LoanService $loanService
    ) {}

    // Thêm sách hoặc cập nhật số lượng đã chọn
    public function add(
        User $member,
        Book $book,
        int $quantity
    ): void {
        // Xác định số bản thực tế có thể mượn
        $available = max(0, min(
            (int) $book->available_copies,
            (int) $book->copies()
                ->where('status', BookCopyStatus::Available->value)
                ->count()
        ));

        if ($quantity < 1 || $quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => __('message.loans.books_unavailable'),
            ]);
        }

        // Thêm mới hoặc cập nhật số lượng nếu sách đã được chọn
        $member->selectedBooks()->syncWithoutDetaching([
            $book->id => ['quantity' => $quantity],
        ]);
    }

    // Xóa một đầu sách khỏi danh sách chọn
    public function remove(User $member, int $bookId): void
    {
        $member->selectedBooks()->detach($bookId);
    }

    // Gửi toàn bộ danh sách chọn để tạo phiếu mượn
    public function submit(User $member): void
    {
        DB::transaction(function () use ($member) {
            // Lấy danh sách sách cùng số lượng đã chọn
            $selections = $member->selectedBooks()
                ->get(['books.id']);

            // Chuyển số lượng thành danh sách ID sách
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

            // Tạo phiếu mượn chính thức
            $this->loanService->requestLoan(
                $member->id,
                $bookIds
            );

            // Chỉ xóa các lựa chọn sau khi tạo phiếu thành công
            $member->selectedBooks()->detach(
                $selections->modelKeys()
            );
        });
    }
}
