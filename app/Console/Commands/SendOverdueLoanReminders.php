<?php

namespace App\Console\Commands;

use App\Events\LoanOverdueReminderRequested;
use App\Models\Loan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOverdueLoanReminders extends Command
{
    protected $signature = 'loans:send-overdue-reminders';

    protected $description = 'Đưa yêu cầu gửi email nhắc trả sách quá hạn vào hàng đợi';

    public function handle(): int
    {
        $queued = 0;
        $failed = 0;

        // Tìm các phiếu mượn đủ điều kiện nhận email
        Loan::query()
            ->forLoanReminder()
            ->where('due_at', '<', now())
            ->whereNull('overdue_reminder_sent_at')
            ->select('loans.id')
            ->chunkById(100, function ($loans) use (&$queued, &$failed) {
                foreach ($loans as $loan) {
                    try {
                        // Phát Event để Laravel đưa Listener vào Queue
                        LoanOverdueReminderRequested::dispatch(
                            $loan->id
                        );

                        $queued++;
                    } catch (Throwable $exception) {
                        $failed++;

                        Log::error(
                            'Failed to queue overdue loan reminder',
                            [
                                'loan_id' => $loan->id,
                                'error' => $exception->getMessage(),
                            ]
                        );
                    }
                }
            });

        $this->info(
            "Đã đưa {$queued} yêu cầu nhắc quá hạn vào hàng đợi."
        );

        if ($failed > 0) {
            $this->error(
                "Có {$failed} yêu cầu không thể đưa vào hàng đợi."
            );
        }

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
