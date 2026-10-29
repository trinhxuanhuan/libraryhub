<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class LoanOverdueReminderRequested
{
    use Dispatchable;

    public function __construct(
        public readonly int $loanId
    ) {}
}
