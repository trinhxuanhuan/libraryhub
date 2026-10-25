<?php

namespace App\Http\Controllers\Management;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        $statistics = [
            'books' => Book::query()->count(),

            'members' => User::query()
                ->where('role', UserRole::Member->value)
                ->count(),

            'borrowed' => Loan::query()
                ->where('status', 'borrowed')
                ->count(),

            'overdue' => Loan::query()
                ->where('status', 'borrowed')
                ->where('due_at', '<', now())
                ->count(),
        ];

        // Tổng hợp lượt giao phiếu trong 6 tháng
        // Bao gồm tháng hiện tại.
        $startMonth = now()->startOfMonth()->subMonths(5);

        $monthlyCounts = Loan::query()
            ->whereNotNull('borrowed_at')
            ->whereBetween('borrowed_at', [
                $startMonth,
                now(),
            ])
            ->selectRaw(
                'SUBSTR(borrowed_at, 1, 7) AS month_key, COUNT(*) AS total'
            )
            ->groupByRaw('SUBSTR(borrowed_at, 1, 7)')
            ->pluck('total', 'month_key');

        // Đảm bảo cả tháng không có lượt mượn cũng xuất hiện,
        // với giá trị bằng 0.
        $monthlyBorrowings = collect(range(0, 5))
            ->map(function (int $index) use ($startMonth, $monthlyCounts) {
                $month = $startMonth->copy()->addMonths($index);

                return [
                    'label' => 'T'.$month->month,
                    'year' => $month->year,
                    'count' => (int) (
                        $monthlyCounts[$month->format('Y-m')] ?? 0
                    ),
                ];
            });

        // Top 5 đầu sách có lượt giao thực tế cao nhất.
        $topBooks = DB::table('loan_items as li')
            ->join('loans as l', 'l.id', '=', 'li.loan_id')
            ->join('books as b', 'b.id', '=', 'li.book_id')
            ->whereNotNull('li.book_copy_id')
            ->whereNotNull('l.borrowed_at')
            ->select('b.id', 'b.title')
            ->selectRaw('COUNT(li.id) AS borrow_count')
            ->groupBy('b.id', 'b.title')
            ->orderByDesc('borrow_count')
            ->orderBy('b.id')
            ->limit(5)
            ->get();

        return view('management.dashboard.index', compact(
            'statistics',
            'monthlyBorrowings',
            'topBooks'
        ));
    }
}
