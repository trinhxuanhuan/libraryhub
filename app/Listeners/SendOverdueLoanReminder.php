<?php

namespace App\Listeners;

use App\Events\LoanOverdueReminderRequested;
use App\Mail\OverdueLoanReminder;
use App\Models\Loan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Throwable;

class SendOverdueLoanReminder implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $backoff = 60;

    // Giới hạn thời gian xử lý một job
    public int $timeout = 60;

    // Không cho nhiều worker gửi email quá hạn cho cùng một phiếu mượn tại cùng thời điểm.
    public function middleware(
        LoanOverdueReminderRequested $event
    ): array {
        return [
            (new WithoutOverlapping(
                'loan:overdue:'.$event->loanId
            ))
                ->shared()
                ->dontRelease()
                ->expireAfter(80),
        ];
    }

    public function handle(
        LoanOverdueReminderRequested $event
    ): void {
        // Kiểm tra lại điều kiện trước khi gửi
        $loan = Loan::query()
            ->forLoanReminder()
            ->with(['user', 'items.book'])
            ->whereKey($event->loanId)
            ->where('due_at', '<', now())
            ->whereNull('overdue_reminder_sent_at')
            ->first();

        if ($loan === null) {
            return;
        }

        // Bắt đầu đo thời gian gửi một email
        $startedAt = hrtime(true);
        $sendSucceeded = false;

        try {
            Mail::to($loan->user->email)
                ->send(new OverdueLoanReminder($loan));

            $sendSucceeded = true;
        } catch (UnexpectedResponseException $exception) {
            $code = $exception->getCode();

            // SMTP 5xx: lỗi vĩnh viễn, không retry
            if ($code >= 500 && $code < 600) {
                $this->fail($exception);

                return;
            }

            // Lỗi tạm thời: để Queue retry
            throw $exception;
        } finally {
            $durationMs = round(
                (hrtime(true) - $startedAt) / 1_000_000,
                2
            );
        }

        // Chỉ đánh dấu khi gửi thành công
        $loan->forceFill([
            'overdue_reminder_sent_at' => now(),
        ])->save();

        // Ghi log sau khi đã lưu trạng thái gửi thành công
        Log::info('Loan reminder mail attempt', [
            'loan_id' => $loan->id,
            'type' => 'overdue',
            'mailer' => config('mail.default'),
            'duration_ms' => $durationMs,
            'send_succeeded' => $sendSucceeded,
        ]);
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
