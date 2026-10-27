<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanItem extends Model
{
    protected $fillable = [
        'loan_id',
        'book_id',
        'book_copy_id',
        'returned_at',
        'return_condition',
        'resolved_at',
        'overdue_fine_amount',
        'compensation_amount',
        'fine_paid_at',
        'return_note',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'fine_paid_at' => 'datetime',
            'overdue_fine_amount' => 'integer',
            'compensation_amount' => 'integer',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    // Member yêu cầu mượn đầu sách nào
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class)->withTrashed();
    }

    // Bản sách vật lý nào được giao
    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }
}
