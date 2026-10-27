<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Illuminate\Console\Command;

class MarkOverdueLoans extends Command
{
    protected $signature = 'loans:mark-overdue';

    protected $description = 'Đánh dấu các phiếu mượn sách quá hạn';

    public function handle(): int
    {
        // Chỉ cập nhật những phiếu:
        // Đang mượn
        // Đã qua hạn trả
        // Chưa được đánh dấu quá hạn
        $count = Loan::query()
            ->where('status', 'borrowed')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNull('overdue_at')
            ->update([
                'overdue_at' => now(),
            ]);

        $this->info(
            "Đã đánh dấu {$count} phiếu mượn quá hạn."
        );

        return self::SUCCESS;
    }
}
