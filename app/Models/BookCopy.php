<?php

namespace App\Models;

use App\Enums\BookCopyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookCopy extends Model
{
    protected $fillable = [
        'book_id',
        'barcode',
        'status',
        'condition_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookCopyStatus::class,
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    // Bản sách này đã xuất hiện trong những lượt mượn nào
    public function loanItems(): HasMany
    {
        return $this->hasMany(LoanItem::class, 'book_copy_id');
    }
}
