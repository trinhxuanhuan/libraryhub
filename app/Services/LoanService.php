<?php

namespace App\Services;

use App\Enums\BookCopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\LoanItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanService
{
    public function requestLoan(int $memberId, array $bookIds): Loan
    {
        return DB::transaction(function () use ($memberId, $bookIds) {
            // Kiểm tra sách còn khả dụng tại lúc tạo yêu cầu
            $availableBooksCount = Book::query()
                ->whereIn('id', $bookIds)
                ->where('available_copies', '>', 0)
                ->whereHas('copies', function (Builder $query) {
                    $query->where(
                        'status',
                        BookCopyStatus::Available->value
                    );
                })
                ->count();

            if ($availableBooksCount !== count($bookIds)) {
                throw ValidationException::withMessages([
                    'book_ids' => __('message.loans.books_unavailable'),
                ]);
            }

            // Tạo phiếu mượn
            $loan = Loan::create([
                'user_id' => $memberId,
                'status' => 'pending',
            ]);

            // Tạo các chi tiết phiếu mượn
            $items = array_map(
                fn (int $bookId) => [
                    'book_id' => $bookId,
                    'book_copy_id' => null,
                ],
                $bookIds
            );

            $loan->items()->createMany($items);

            return $loan;
        });
    }

    public function issueLoan(int $loanId, array $copyIds, int $librarianId): void
    {
        DB::transaction(function () use ($loanId, $copyIds, $librarianId) {
            // Khóa phiếu mượn để tránh xử lý đồng thời
            $loan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($loanId);

            if ($loan->status !== 'pending') {
                throw ValidationException::withMessages([
                    'loan' => 'Chỉ được duyệt phiếu đang chờ xử lý.',
                ]);
            }

            $items = $loan->items()
                ->orderBy('book_id')
                ->lockForUpdate()
                ->get();

            // Mỗi LoanItem phải được chọn đúng một BookCopy
            $expectedIds = $items->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $providedIds = collect(array_keys($copyIds))
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if (
                $items->isEmpty() ||
                $expectedIds !== $providedIds ||
                count(array_unique(array_map('intval', array_values($copyIds))))
                    !== count($copyIds)
            ) {
                throw ValidationException::withMessages([
                    'copies' => 'Cần chọn đúng một bản sách cho mỗi đầu sách.',
                ]);
            }

            foreach ($items as $item) {
                // Khóa đầu sách trước khi thay đổi số lượng
                $book = Book::query()
                    ->lockForUpdate()
                    ->findOrFail($item->book_id);

                $copyId = (int) $copyIds[$item->id];

                // Bản sách phải thuộc đúng đầu sách của LoanItem
                $copy = BookCopy::query()
                    ->where('book_id', $book->id)
                    ->whereKey($copyId)
                    ->lockForUpdate()
                    ->first();

                if (
                    $copy === null ||
                    $copy->getRawOriginal('status')
                        !== BookCopyStatus::Available->value ||
                    (int) $book->available_copies < 1
                ) {
                    throw ValidationException::withMessages([
                        'copies' => "Không thể giao sách: {$book->title}.",
                    ]);
                }

                // Đánh dấu bản vật lý đã được mượn
                $copy->update([
                    'status' => BookCopyStatus::Borrowed->value,
                ]);

                // Ghi nhận bản nào đã giao
                $item->update([
                    'book_copy_id' => $copy->id,
                ]);

                // Giảm số bản khả dụng
                $book->decrement('available_copies');
            }

            $borrowedAt = now();

            // Tạm áp dụng thời hạn mượn 14 ngày.
            $loan->update([
                'status' => 'borrowed',
                'issued_by' => $librarianId,
                'borrowed_at' => $borrowedAt,
                'due_at' => $borrowedAt->copy()->addDays(14),
            ]);
        });
    }

    public function rejectLoan(int $loanId): void
    {
        DB::transaction(function () use ($loanId) {
            $loan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($loanId);

            if ($loan->status !== 'pending') {
                throw ValidationException::withMessages([
                    'loan' => 'Chỉ được từ chối phiếu đang chờ xử lý.',
                ]);
            }

            $loan->update([
                'status' => 'rejected',
            ]);
        });
    }

    public function returnItem(int $loanId, int $itemId): void
    {
        DB::transaction(function () use ($loanId, $itemId) {
            $loan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($loanId);

            if ($loan->status !== 'borrowed') {
                throw ValidationException::withMessages([
                    'loan' => 'Phiếu này không ở trạng thái đang mượn.',
                ]);
            }

            // Chỉ lấy LoanItem thuộc phiếu đang xử lý
            $item = LoanItem::query()
                ->where('loan_id', $loan->id)
                ->lockForUpdate()
                ->findOrFail($itemId);

            if (
                $item->returned_at !== null ||
                $item->book_copy_id === null
            ) {
                throw ValidationException::withMessages([
                    'loan' => 'Bản sách này đã trả hoặc chưa được giao.',
                ]);
            }

            // Bao gồm cả sách đã bị soft delete nếu có
            $book = Book::withTrashed()
                ->whereKey($item->book_id)
                ->lockForUpdate()
                ->firstOrFail();

            $copy = BookCopy::query()
                ->whereKey($item->book_copy_id)
                ->where('book_id', $book->id)
                ->lockForUpdate()
                ->first();

            if (
                $copy === null ||
                $copy->getRawOriginal('status')
                    !== BookCopyStatus::Borrowed->value ||
                (int) $book->available_copies >= (int) $book->total_copies
            ) {
                throw ValidationException::withMessages([
                    'loan' => 'Trạng thái bản sách không hợp lệ.',
                ]);
            }

            // Bản vật lý trở lại trạng thái có thể mượn
            $copy->update([
                'status' => BookCopyStatus::Available->value,
            ]);

            // Tăng số lượng sách khả dụng
            $book->increment('available_copies');

            // Ghi nhận thời điểm trả của từng đầu sách
            $item->update([
                'returned_at' => now(),
            ]);

            // Nếu tất cả LoanItem đã trả thì hoàn thành phiếu
            $hasUnreturnedItems = $loan->items()
                ->whereNull('returned_at')
                ->exists();

            if (! $hasUnreturnedItems) {
                $loan->update([
                    'status' => 'returned',
                    'returned_at' => now(),
                ]);
            }
        });
    }
}
