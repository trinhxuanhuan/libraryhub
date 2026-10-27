<?php

namespace App\Services;

use App\Enums\BookCopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanService
{
    public function requestLoan(int $memberId, array $bookIds): Loan
    {
        return DB::transaction(function () use ($memberId, $bookIds) {
            // Khóa Member để tránh hai yêu cầu mượn được tạo đồng thời
            User::query()
                ->whereKey($memberId)
                ->lockForUpdate()
                ->firstOrFail();

            // Kiểm tra đầu sách đang chờ duyệt hoặc chưa trả
            $alreadyBorrowing = LoanItem::query()
                ->whereIn('book_id', $bookIds)
                ->whereNull('resolved_at')
                ->whereHas('loan', function (Builder $query) use ($memberId) {
                    $query->where('user_id', $memberId)
                        ->whereIn('status', ['pending', 'borrowed']);
                })
                ->exists();

            if ($alreadyBorrowing) {
                throw ValidationException::withMessages([
                    'book_ids' => __('message.loans.already_borrowing'),
                ]);
            }
            // Đếm số bản được yêu cầu theo từng đầu sách
            $requestedQuantities = array_count_values($bookIds);

            if (empty($requestedQuantities)) {
                throw ValidationException::withMessages([
                    'book_ids' => __('message.loans.selection_empty'),
                ]);
            }

            // Lấy số lượng khả dụng của các đầu sách
            $books = Book::query()
                ->select(['id', 'available_copies'])
                ->whereIn('id', array_keys($requestedQuantities))
                ->orderBy('id')
                ->lockForUpdate()
                ->withCount([
                    'copies as available_copy_count' => function (Builder $query) {
                        $query->where(
                            'status',
                            BookCopyStatus::Available->value
                        );
                    },
                ])
                ->get()
                ->keyBy('id');

            // Kiểm tra từng đầu sách có đủ số bản hay không
            foreach ($requestedQuantities as $bookId => $quantity) {
                $book = $books->get($bookId);

                if (
                    $book === null ||
                    (int) $book->available_copies < $quantity ||
                    (int) $book->available_copy_count < $quantity
                ) {
                    throw ValidationException::withMessages([
                        'book_ids' => __('message.loans.books_unavailable'),
                    ]);
                }
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
                $book = Book::withTrashed()
                    ->whereKey($item->book_id)
                    ->lockForUpdate()
                    ->first();

                if ($book === null || $book->trashed()) {
                    throw ValidationException::withMessages([
                        'copies' => 'Một hoặc nhiều đầu sách trong phiếu đã ngừng cung cấp. Không thể giao sách.',
                    ]);
                }

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
                'due_at' => $borrowedAt->copy()->addDays(
                    (int) config('library_loan.loan_duration_days')
                ),
            ]);
        });
    }

    public function rejectLoan(
        int $loanId,
        ?string $reason = null
    ): void {
        DB::transaction(function () use ($loanId, $reason) {
            $loan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($loanId);

            if ($loan->status !== 'pending') {
                throw ValidationException::withMessages([
                    'loan' => __('message.loans.only_pending_can_reject'),
                ]);
            }

            $loan->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);
        });
    }

    public function returnItem(int $loanId, int $itemId, string $condition = 'normal', ?string $note = null): void
    {
        DB::transaction(function () use ($loanId, $itemId, $condition, $note) {
            // 1. Khóa và kiểm tra phiếu mượn
            $loan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($loanId);

            if ($loan->status !== 'borrowed') {
                throw ValidationException::withMessages([
                    'loan' => 'Phiếu không ở trạng thái đang mượn.',
                ]);
            }

            // 2. Kiểm tra tình trạng sách được gửi lên
            if (! in_array($condition, ['normal', 'damaged', 'lost'], true)) {
                throw ValidationException::withMessages([
                    'condition' => 'Tình trạng sách không hợp lệ.',
                ]);
            }

            // 3. Chỉ lấy LoanItem thuộc phiếu hiện tại
            $item = LoanItem::query()
                ->where('loan_id', $loan->id)
                ->whereKey($itemId)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $item->resolved_at !== null ||
                $item->returned_at !== null ||
                $item->book_copy_id === null
            ) {
                throw ValidationException::withMessages([
                    'loan' => 'Bản sách đã được xử lý hoặc chưa được giao.',
                ]);
            }

            // 4. Khóa đầu sách
            $book = Book::withTrashed()
                ->whereKey($item->book_id)
                ->lockForUpdate()
                ->firstOrFail();

            // 5. Khóa bản sách vật lý
            $copy = BookCopy::query()
                ->whereKey($item->book_copy_id)
                ->where('book_id', $book->id)
                ->lockForUpdate()
                ->first();

            if (
                $copy === null ||
                $copy->getRawOriginal('status')
                    !== BookCopyStatus::Borrowed->value
            ) {
                throw ValidationException::withMessages([
                    'loan' => 'Trạng thái bản sách không hợp lệ.',
                ]);
            }

            // 6. Tính thời điểm xử lý và số ngày quá hạn
            $resolvedAt = now();

            $daysLate = 0;

            if (
                $loan->due_at !== null &&
                $resolvedAt->greaterThan($loan->due_at)
            ) {
                // Phần lẻ của một ngày được tính thành một ngày
                $daysLate = (int) ceil(
                    $loan->due_at->diffInSeconds($resolvedAt) / 86400
                );
            }

            $overdueFine = $daysLate *
                (int) config('library_loan.overdue_fine_per_day', 0);

            // 7. Xác định tiền bồi thường
            $compensation = match ($condition) {
                'damaged' => (int) config(
                    'library_loan.damaged_compensation',
                    0
                ),
                'lost' => (int) config(
                    'library_loan.lost_compensation',
                    0
                ),
                default => 0,
            };

            // 8. Xử lý bản sách theo tình trạng thực tế
            $copyStatus = match ($condition) {
                'normal' => BookCopyStatus::Available->value,
                'damaged' => BookCopyStatus::Damaged->value,
                'lost' => BookCopyStatus::Lost->value,
            };

            if ($condition === 'normal') {
                if (
                    (int) $book->available_copies >=
                    (int) $book->total_copies
                ) {
                    throw ValidationException::withMessages([
                        'loan' => 'Số lượng sách khả dụng không hợp lệ.',
                    ]);
                }
            }

            $copy->update([
                'status' => $copyStatus,
            ]);

            // Chỉ sách bình thường mới quay lại kho khả dụng
            if ($condition === 'normal') {
                $book->increment('available_copies');
            }

            // 9. Ghi nhận kết quả xử lý LoanItem
            $item->update([
                'return_condition' => $condition,
                'returned_at' => $condition === 'lost'
                    ? null
                    : $resolvedAt,
                'resolved_at' => $resolvedAt,
                'overdue_fine_amount' => $overdueFine,
                'compensation_amount' => $compensation,
                'return_note' => $note,
            ]);

            // 10. Kiểm tra tất cả LoanItems đã xử lý chưa
            $hasUnresolvedItems = $loan->items()
                ->whereNull('resolved_at')
                ->exists();

            if (! $hasUnresolvedItems) {
                $loan->update([
                    'status' => 'returned',
                    'returned_at' => $resolvedAt,
                ]);
            }
        });
    }

    public function renewLoan(int $loanId, int $memberId): void
    {
        DB::transaction(function () use ($loanId, $memberId) {

            // Khóa phiếu để tránh hai yêu cầu gia hạn cùng lúc
            $loan = Loan::query()
                ->where('user_id', $memberId)
                ->whereKey($loanId)
                ->lockForUpdate()
                ->firstOrFail();

            // Chỉ gia hạn phiếu đang mượn
            if ($loan->status !== 'borrowed') {
                throw ValidationException::withMessages([
                    'loan' => 'Chỉ được gia hạn phiếu đang mượn.',
                ]);
            }

            // Không cho gia hạn khi đã hết hạn
            if ($loan->due_at === null || $loan->due_at->isPast()) {
                throw ValidationException::withMessages([
                    'loan' => 'Phiếu đã quá hạn hoặc không có hạn trả.',
                ]);
            }

            // Mỗi phiếu chỉ được gia hạn một lần
            if ($loan->renewal_count >= 1) {
                throw ValidationException::withMessages([
                    'loan' => 'Phiếu đã hết số lần gia hạn.',
                ]);
            }

            // Không gia hạn phiếu trống hoặc đã trả một phần
            if (
                ! $loan->items()->exists() ||
                $loan->items()->whereNotNull('returned_at')->exists()
            ) {
                throw ValidationException::withMessages([
                    'loan' => 'Không thể gia hạn phiếu đã trả một phần sách.',
                ]);
            }

            // Cộng thêm số ngày gia hạn từ hạn trả hiện tại
            // Lấy số ngày gia hạn từ config
            $renewalDays = (int) config('library_loan.renewal_days');

            // Cập nhật hạn trả mới
            $loan->due_at = $loan->due_at->copy()->addDays($renewalDays);

            // Tăng số lần gia hạn
            $loan->renewal_count = (int) $loan->renewal_count + 1;

            // Xóa dấu nhắc cũ vì hạn trả đã thay đổi
            $loan->due_soon_reminder_sent_at = null;

            // Lưu tất cả thay đổi vào database
            $loan->save();
        });
    }

    public function markFinePaid(int $loanId, int $itemId): void
    {
        DB::transaction(function () use ($loanId, $itemId) {
            // Khóa Loan trước để giữ thứ tự khóa nhất quán
            $loan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($loanId);

            // Chỉ lấy LoanItem thuộc đúng phiếu
            $item = LoanItem::query()
                ->where('loan_id', $loan->id)
                ->whereKey($itemId)
                ->lockForUpdate()
                ->firstOrFail();

            // Chỉ thu phí sau khi bản sách đã được xử lý
            if ($item->resolved_at === null) {
                throw ValidationException::withMessages([
                    'fine' => 'Bản sách chưa được xử lý trả hoặc mất.',
                ]);
            }

            // Tính tổng số tiền phải thanh toán
            $totalFine = (int) $item->overdue_fine_amount
                + (int) $item->compensation_amount;

            if ($totalFine <= 0) {
                throw ValidationException::withMessages([
                    'fine' => 'Bản sách này không phát sinh phí phạt.',
                ]);
            }

            // Không được xác nhận thanh toán hai lần
            if ($item->fine_paid_at !== null) {
                throw ValidationException::withMessages([
                    'fine' => 'Khoản phí này đã được thanh toán.',
                ]);
            }

            // Ghi nhận thời điểm Librarian xác nhận đã thu tiền
            $item->update([
                'fine_paid_at' => now(),
            ]);
        });
    }
}
