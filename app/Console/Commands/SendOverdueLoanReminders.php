<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Mail\OverdueLoanReminder;
use App\Models\Loan;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOverdueLoanReminders extends Command
{
    protected $signature = 'loans:send-overdue-reminders';

    protected $description = 'Gửi email nhắc trả sách cho phiếu mượn quá hạn';

    public function handle(): int
    {
        $sent = 0;
        $failed = 0;

        Loan::query()
            ->with(['user', 'items.book'])
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
            ->chunkById(100, function ($loans) use (&$sent, &$failed) {
                foreach ($loans as $loan) {
                    try {
                        Mail::to($loan->user->email)
                            ->send(new OverdueLoanReminder($loan));

                        $loan->forceFill([
                            'overdue_reminder_sent_at' => now(),
                        ])->save();

                        $sent++;
                    } catch (Throwable $exception) {
                        $failed++;

                        Log::error('Failed to send overdue loan reminder', [
                            'loan_id' => $loan->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("Đã gửi nhắc nhở cho {$sent} phiếu.");

        if ($failed > 0) {
            $this->error("Có {$failed} phiếu gửi nhắc nhở thất bại.");
        }

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
