<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BookRetirementService
{
    public function __construct(
        private readonly LoanService $loanService
    ) {}

    public function retire(Book $book): int
    {
        return DB::transaction(function () use ($book) {
            // Khóa đầu sách để tránh thay đổi đồng thời
            $lockedBook = Book::query()
                ->whereKey($book->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Lấy các phiếu đang chờ có chứa đầu sách này
            $pendingLoans = Loan::query()
                ->where('status', 'pending')
                ->whereHas('items', function (Builder $query) use ($lockedBook) {
                    $query->where('book_id', $lockedBook->id);
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Lý do từ chối
            $reason = __('message.loans.rejected_book_retired', [
                'book' => $lockedBook->title,
            ]);

            // Từ chối từng phiếu bị ảnh hưởng
            foreach ($pendingLoans as $loan) {
                $this->loanService->rejectLoan(
                    $loan->id,
                    $reason
                );
            }

            // Xóa sách khỏi danh sách chọn chưa gửi yêu cầu
            DB::table('book_selections')
                ->where('book_id', $lockedBook->id)
                ->delete();

            // Ngừng cung cấp đầu sách
            $lockedBook->delete();

            return $pendingLoans->count();
        }, attempts: 3);
    }
}
