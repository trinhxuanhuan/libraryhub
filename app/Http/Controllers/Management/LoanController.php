<?php

namespace App\Http\Controllers\Management;

use App\Enums\BookCopyStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                'in:pending,borrowed,overdue,returned,rejected',
            ],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? '';
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        if ($fromDate && $toDate && $fromDate > $toDate) {
            throw ValidationException::withMessages([
                'to_date' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi.',
            ]);
        }

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

        // Lọc theo trạng thái
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

        // Khoảng ngày yêu cầu mượn
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        // Ưu tiên chờ xử lý, tiếp theo là quá hạn
        $loans = $query
            ->orderByRaw(
                "CASE
                    WHEN status = 'pending' THEN 0
                    WHEN status = 'borrowed' AND due_at < ? THEN 1
                    WHEN status = 'borrowed' THEN 2
                    ELSE 3
                END",
                [now()]
            )
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('management.loans.index', [
            'loans' => $loans,
        ]);
    }

    // Xem chi tiết một phiếu mượn
    public function show(Loan $loan): View
    {
        $loan->load([
            'user',
            'items.book',
            'items.bookCopy',
        ]);

        // Lấy các bản sách có thể giao cho Member
        $availableCopiesByBook = collect();

        if ($loan->status === 'pending') {
            $availableCopiesByBook = BookCopy::query()
                ->whereIn('book_id', $loan->items->pluck('book_id'))
                ->where('status', BookCopyStatus::Available->value)
                ->orderBy('barcode')
                ->get(['id', 'book_id', 'barcode'])
                ->groupBy('book_id');
        }

        return view('management.loans.show', [
            'loan' => $loan,
            'availableCopiesByBook' => $availableCopiesByBook,
        ]);
    }

    // Duyệt và giao sách
    public function issue(
        Request $request,
        Loan $loan,
        LoanService $loanService
    ): RedirectResponse {
        $data = $request->validate([
            'copies' => ['required', 'array'],
            'copies.*' => [
                'required',
                'integer',
                'distinct',
                'exists:book_copies,id',
            ],
        ], [
            'copies.*.distinct' => __('message.loans.duplicate_physical_copy'),
        ]);

        $librarianId = (int) $request->session()
            ->get('manual_auth_user_id');

        $loanService->issueLoan(
            $loan->id,
            $data['copies'],
            $librarianId
        );

        return redirect()
            ->route('management.loans.show', $loan)
            ->with('success', 'Đã duyệt và giao sách thành công.');
    }

    // Từ chối yêu cầu mượn
    public function reject(
        Loan $loan,
        LoanService $loanService
    ): RedirectResponse {
        $loanService->rejectLoan($loan->id);

        return redirect()
            ->route('management.loans.show', $loan)
            ->with('success', 'Đã từ chối yêu cầu mượn sách.');
    }

    // Xác nhận trả một bản sách
    public function returnItem(Request $request, Loan $loan, LoanItem $item, LoanService $loanService): RedirectResponse
    {
        $validated = $request->validate([
            'condition' => [
                'sometimes',
                'in:normal,damaged,lost',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $loanService->returnItem(
            $loan->id,
            $item->id,
            $validated['condition'] ?? 'normal',
            $validated['note'] ?? null
        );

        return redirect()
            ->route('management.loans.show', $loan)
            ->with('success', 'Đã ghi nhận xử lý bản sách thành công.');
    }

    public function confirmFinePayment(Loan $loan, LoanItem $item, LoanService $loanService): RedirectResponse
    {
        $loanService->markFinePaid(
            $loan->id,
            $item->id
        );

        return redirect()
            ->route('management.loans.show', $loan)
            ->with('success', 'Đã xác nhận thu tiền phạt thành công.');
    }

    public function create(): View
    {
        // Chỉ hiển thị thành viên đang hoạt động
        $members = User::query()
            ->where('role', UserRole::Member->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Chỉ hiển thị đầu sách có bản vật lý khả dụng
        $books = Book::query()
            ->where('available_copies', '>', 0)
            ->whereHas('copies', function ($query) {
                $query->where(
                    'status',
                    BookCopyStatus::Available->value
                );
            })
            ->orderBy('title')
            ->get(['id', 'title', 'available_copies']);

        return view('management.loans.create', compact(
            'members',
            'books'
        ));
    }

    public function store(Request $request, LoanService $loanService): RedirectResponse
    {
        $validated = $request->validate([
            'member_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', UserRole::Member->value)
                    ->where('is_active', true),
            ],
            'book_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'book_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('books', 'id')
                    ->whereNull('deleted_at'),
            ],
        ]);

        // Dùng lại nghiệp vụ tạo phiếu có sẵn
        $loan = $loanService->requestLoan(
            (int) $validated['member_id'],
            array_map('intval', $validated['book_ids'])
        );

        return redirect()
            ->route('management.loans.show', $loan)
            ->with(
                'success',
                'Đã tạo phiếu mượn. Hãy chọn bản sách để giao.'
            );
    }
}
