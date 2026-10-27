<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\LoanOverdueReminderRequested;
use App\Mail\OverdueLoanReminder;
use App\Models\Loan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOverdueLoanReminder implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $backoff = 60;

    public function handle(
        LoanOverdueReminderRequested $event
    ): void {
        // Kiểm tra lại điều kiện trước khi gửi
        $loan = Loan::query()
            ->with(['user', 'items.book'])
            ->whereKey($event->loanId)
            ->where('status', 'borrowed')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNull('overdue_reminder_sent_at')
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

        // Gửi email nhắc trả quá hạn
        Mail::to($loan->user->email)
            ->send(new OverdueLoanReminder($loan));

        // Chỉ đánh dấu khi gửi thành công
        $loan->forceFill([
            'overdue_reminder_sent_at' => now(),
        ])->save();
    }

    public function failed(
        LoanOverdueReminderRequested $event,
        Throwable $exception
    ): void {
        Log::error('Overdue loan reminder job failed', [
            'loan_id' => $event->loanId,
            'error' => $exception->getMessage(),
        ]);
    }
}
