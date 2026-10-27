<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\LoanDueSoonReminderRequested;
use App\Mail\DueSoonLoanReminder;
use App\Models\Loan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDueSoonLoanReminder implements ShouldQueue
{
    use InteractsWithQueue;

    // Tối đa 3 lần thử gửi
    public int $tries = 3;

    // Chờ 60 giây trước khi thử lại
    public int $backoff = 60;

    public function handle(
        LoanDueSoonReminderRequested $event
    ): void {
        $days = max(
            1,
            (int) config('library_loan.due_soon_reminder_days', 2)
        );

        // Giới hạn khoảng nhắc theo ngày Việt Nam
        $endExclusive = now('Asia/Ho_Chi_Minh')
            ->startOfDay()
            ->addDays($days + 1)
            ->setTimezone(config('app.timezone', 'UTC'));

        // Kiểm tra lại trạng thái trước khi gửi
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

        // Worker thực hiện gửi email
        Mail::to($loan->user->email)
            ->send(new DueSoonLoanReminder($loan));

        // Chỉ ghi nhận sau khi gửi không phát sinh lỗi
        $loan->forceFill([
            'due_soon_reminder_sent_at' => now(),
        ])->save();
    }

    // Được gọi khi Job thất bại sau các lần thử
    public function failed(
        LoanDueSoonReminderRequested $event,
        Throwable $exception
    ): void {
        Log::error('Due-soon loan reminder job failed', [
            'loan_id' => $event->loanId,
            'error' => $exception->getMessage(),
        ]);
    }
}
