<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'rejection_reason',
        'issued_by',
        'borrowed_at',
        'due_at',
        'returned_at',
        'renewal_count',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
            'renewal_count' => 'integer',
            'overdue_at' => 'datetime',
            'overdue_reminder_sent_at' => 'datetime',
            'due_soon_reminder_sent_at' => 'datetime',
        ];
    }

    // Điều kiện chung để một phiếu có thể được xét gửi email nhắc hạn.
    // Chưa kiểm tra thời điểm nhắc và loại email đã gửi
    public function scopeForLoanReminder(Builder $query): Builder
    {
        return $query
            ->where('status', 'borrowed')
            ->whereNotNull('due_at')
            ->whereHas('user', function (Builder $userQuery) {
                $userQuery
                    ->where('role', UserRole::Member->value)
                    ->where('is_active', true)
                    ->whereNotNull('email')
                    ->where('email', '!=', '');
            });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }

    // Thủ thư xác nhận giao sách
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
