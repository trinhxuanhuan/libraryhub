<?php

namespace App\Console\Commands;

use App\Events\LoanDueSoonReminderRequested;
use App\Models\Loan;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDueSoonLoanReminders extends Command
{
    protected $signature = 'loans:send-due-soon-reminders';

    protected $description = 'Nhắc thành viên trả sách trước hạn';

    public function handle(): int
    {
        $days = max(
            1,
            (int) config('library_loan.due_soon_reminder_days', 2)
        );

        // Lấy cuối ngày thứ N theo giờ Việt Nam
        $endExclusive = now('Asia/Ho_Chi_Minh')
            ->startOfDay()
            ->addDays($days + 1)
            ->setTimezone(config('app.timezone', 'UTC'));

        $dispatched = 0;
        $failed = 0;

        Loan::query()
            ->forLoanReminder()
            ->where('due_at', '>', now())
            ->where('due_at', '<', $endExclusive)
            ->whereNull('due_soon_reminder_sent_at')
            ->whereHas('items', function (Builder $query) {
                $query->whereNull('resolved_at');
            })
            ->select('loans.id')
            ->chunkById(100, function ($loans) use (
                &$dispatched,
                &$failed
            ) {
                foreach ($loans as $loan) {
                    try {
                        LoanDueSoonReminderRequested::dispatch(
                            $loan->id
                        );

                        $dispatched++;
                    } catch (Throwable $exception) {
                        $failed++;

                        Log::error(
                            'Failed to process due-soon loan reminder',
                            [
                                'loan_id' => $loan->id,
                                'error' => $exception->getMessage(),
                            ]
                        );
                    }
                }
            });

        $this->info(
            "Đã xử lý {$dispatched} yêu cầu nhắc trước hạn."
        );

        if ($failed > 0) {
            $this->error(
                "Có {$failed} yêu cầu xử lý thất bại."
            );
        }

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
