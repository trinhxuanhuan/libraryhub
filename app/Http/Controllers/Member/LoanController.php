<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoanRequest;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanController extends Controller
{
    // Xử lý yêu cầu mượn sách
    public function store(StoreLoanRequest $request, LoanService $loanService): RedirectResponse
    {
        $bookIds = array_map('intval', $request->validated('book_ids'));
        $memberId = (int) $request->session()
            ->get('manual_auth_user_id');

        $loanService->requestLoan($memberId, $bookIds);

        return redirect()
            ->route('member.loans.index')
            ->with('success', __('message.loans.request_created'));
    }

    // Xem các phiếu mượn của Member đang đăng nhập
    public function myLoans(Request $request): View
    {
        $memberId = (int) $request->session()
            ->get('manual_auth_user_id');

        $loans = Loan::query()
            ->with('items.book')
            ->where('user_id', $memberId)
            ->orderByDesc('id')
            ->get();

        return view('member.loans.index', compact('loans'));
    }

    public function renew(Request $request, Loan $loan, LoanService $loanService): RedirectResponse
    {
        $memberId = (int) $request->session()
            ->get('manual_auth_user_id');

        $loanService->renewLoan($loan->id, $memberId);

        return redirect()
            ->route('member.loans.index')
            ->with('success', __('message.loans.renewed'));
    }
    public function cancel(
        Request $request,
        Loan $loan,
        LoanService $loanService
    ): RedirectResponse {
        $memberId = (int) $request->session()
            ->get('manual_auth_user_id');

        $loanService->cancelLoan($loan->id, $memberId);

        return redirect()
            ->route('member.loans.index')
            ->with('success', __('message.loans.cancelled'));
    }
}
