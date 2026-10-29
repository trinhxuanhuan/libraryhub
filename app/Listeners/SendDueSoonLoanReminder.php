<?php

namespace App\Listeners;

use App\Events\LoanDueSoonReminderRequested;
use App\Mail\DueSoonLoanReminder;
use App\Models\Loan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Throwable;

class SendDueSoonLoanReminder implements ShouldQueue
{
    use InteractsWithQueue;

    // Tối đa 3 lần thử gửi
    public int $tries = 3;

    // Chờ 60 giây trước khi thử lại
    public int $backoff = 60;

    public int $timeout = 60;

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

        // Kiểm tra lại điều kiện trước khi gửi
        $loan = Loan::query()
            ->forLoanReminder()
            ->with(['user', 'items.book'])
            ->whereKey($event->loanId)
            ->where('due_at', '>', now())
            ->where('due_at', '<', $endExclusive)
            ->whereNull('due_soon_reminder_sent_at')
            ->whereHas('items', function (Builder $query) {
                $query->whereNull('resolved_at');
            })
            ->first();

        if ($loan === null) {
            return;
        }

        // Bắt đầu đo thời gian gửi một email
        $startedAt = hrtime(true);
        $sendSucceeded = false;

        try {
            Mail::to($loan->user->email)
                ->send(new DueSoonLoanReminder($loan));

            $sendSucceeded = true;
        } catch (UnexpectedResponseException $exception) {
            $smtpCode = $exception->getCode();

            if ($smtpCode >= 500 && $smtpCode < 600) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        } finally {
            $durationMs = round(
                (hrtime(true) - $startedAt) / 1_000_000,
                2
            );
        }

        // Chỉ đánh dấu sau khi SMTP chấp nhận email
        $loan->forceFill([
            'due_soon_reminder_sent_at' => now(),
        ])->save();

        // Ghi log sau khi đã lưu trạng thái gửi thành công
        Log::info('Loan reminder mail attempt', [
            'loan_id' => $loan->id,
            'type' => 'due_soon',
            'mailer' => config('mail.default'),
            'duration_ms' => $durationMs,
            'send_succeeded' => $sendSucceeded,
        ]);
    }

    // Tránh gửi trùng email nhắc sắp đến hạn.
    public function middleware(
        LoanDueSoonReminderRequested $event
    ): array {
        return [
            (new WithoutOverlapping(
                'loan:due-soon:'.$event->loanId
            ))
                ->shared()
                ->dontRelease()
                ->expireAfter(80),
        ];
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
