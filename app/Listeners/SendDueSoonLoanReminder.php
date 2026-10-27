<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\LoanDueSoonReminderRequested;
use App\Mail\DueSoonLoanReminder;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class SendDueSoonLoanReminder
{
    public function handle(
        LoanDueSoonReminderRequested $event
    ): void {
        $days = max(
            1,
            (int) config('library_loan.due_soon_reminder_days', 2)
        );

        // Giới hạn cuối của khoảng nhắc, theo ngày Việt Nam
        $endExclusive = now('Asia/Ho_Chi_Minh')
            ->startOfDay()
            ->addDays($days + 1)
            ->setTimezone(config('app.timezone', 'UTC'));

        // Kiểm tra lại trạng thái thực tế trước khi gửi
        $loan = Loan::query()
            ->with(['user', 'items.book'])
            ->whereKey($event->loanId)
            ->where('status', 'borrowed')
            ->whereNotNull('due_at')
            ->where('due_at', '>', now())
            ->where('due_at', '<', $endExclusive)
            ->whereNull('due_soon_reminder_sent_at')
            ->whereHas('items', function (Builder $query) {
                $query->whereNull('resolved_at');
            })
            ->whereHas('user', function (Builder $query) {
                $query
                    ->where('role', UserRole::Member->value)
                    ->where('is_active', true)
                    ->whereNotNull('email')
                    ->where('email', '!=', '');
            })
            ->first();

        if ($loan === null) {
            return;
        }

        // Gửi email thực tế
        Mail::to($loan->user->email)
            ->send(new DueSoonLoanReminder($loan));

        // Chỉ đánh dấu sau khi không phát sinh lỗi gửi email
        $loan->forceFill([
            'due_soon_reminder_sent_at' => now(),
        ])->save();
    }
}
