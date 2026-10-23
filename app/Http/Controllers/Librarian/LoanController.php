<?php

namespace App\Http\Controllers\Librarian;

use App\Enums\BookCopyStatus;
use App\Http\Controllers\Controller;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Services\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(): View
    {
        $loans = Loan::query()
            ->with(['user'])
            ->withCount('items')
            ->orderByRaw("
                CASE
                    WHEN status = 'pending' THEN 0
                    WHEN status = 'borrowed' THEN 1
                    ELSE 2
                END
            ")
            ->latest('id')
            ->paginate(15);

        return view('librarian.loans.index', [
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

        return view('librarian.loans.show', [
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
        ]);

        $librarianId = (int) $request->session()
            ->get('manual_auth_user_id');

        $loanService->issueLoan(
            $loan->id,
            $data['copies'],
            $librarianId
        );

        return redirect()
            ->route('librarian.loans.show', $loan)
            ->with('success', 'Đã duyệt và giao sách thành công.');
    }

    // Từ chối yêu cầu mượn
    public function reject(
        Loan $loan,
        LoanService $loanService
    ): RedirectResponse {
        $loanService->rejectLoan($loan->id);

        return redirect()
            ->route('librarian.loans.show', $loan)
            ->with('success', 'Đã từ chối yêu cầu mượn sách.');
    }

    // Xác nhận trả một bản sách
    public function returnItem(
        Loan $loan,
        LoanItem $item,
        LoanService $loanService
    ): RedirectResponse {
        $loanService->returnItem($loan->id, $item->id);

        return redirect()
            ->route('librarian.loans.show', $loan)
            ->with('success', 'Đã xác nhận trả sách thành công.');
    }
}
