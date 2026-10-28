<?php

namespace App\Queries;

use App\Enums\LoanStatus;
use App\Models\Loan;
use Illuminate\Pagination\LengthAwarePaginator;

class LoanListQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? '';
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        $query = Loan::query()
            ->with([
                'user',
                'items.book',
            ])
            ->withCount('items');

        // Tìm theo mã phiếu, tên hoặc email thành viên
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });

                if (ctype_digit($search)) {
                    $q->orWhere('loans.id', (int) $search);
                }
            });
        }

        // Lọc theo trạng thái phiếu
        if ($status === 'overdue') {
            $query->where('status', 'borrowed')
                ->where('due_at', '<', now());
        } elseif ($status === 'borrowed') {
            $query->where('status', 'borrowed')
                ->where(function ($q) {
                    $q->whereNull('due_at')
                        ->orWhere('due_at', '>=', now());
                });
        } elseif ($status !== '') {
            $query->where('status', $status);
        }

        // Lọc theo khoảng ngày tạo yêu cầu
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        // Ưu tiên phiếu chờ duyệt, sau đó đến phiếu quá hạn
        return $query
            ->orderByRaw(
                "CASE
                    WHEN status = 'pending' THEN 0
                    WHEN status = 'borrowed' AND due_at < ? THEN 1
                    WHEN status = 'borrowed' THEN 2
                    ELSE 3
                END",
                [now()]
            )
            // Phiếu pending: ưu tiên người gửi yêu cầu trước
            ->orderByRaw(
                'CASE WHEN status = ? THEN created_at END ASC',
                [LoanStatus::Pending->value]
            )
            ->orderByRaw(
                'CASE WHEN status = ? THEN id END ASC',
                [LoanStatus::Pending->value]
            )
            ->latest('id')
            ->paginate(15);
    }
}
