<?php

namespace Tests\Feature\Console;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MarkOverdueLoansTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        // Cố định thời gian để kết quả test không thay đổi
        $this->travelTo(
            Carbon::parse('2026-10-25 12:00:00')
        );

        $this->member = User::factory()->create();
    }

    private function createLoan(
        string $status,
        ?Carbon $dueAt
    ): Loan {
        return Loan::create([
            'user_id' => $this->member->id,
            'status' => $status,
            'due_at' => $dueAt,
        ]);
    }

    public function test_command_only_marks_overdue_borrowed_loans(): void
    {
        // Phiếu đang mượn đã quá hạn
        $overdue = $this->createLoan(
            'borrowed',
            now()->subDay()
        );

        // Phiếu đang mượn chưa quá hạn
        $notDue = $this->createLoan(
            'borrowed',
            now()->addDay()
        );

        // Phiếu đang chờ xử lý
        $pending = $this->createLoan(
            'pending',
            now()->subDay()
        );

        // Phiếu đã hoàn tất
        $returned = $this->createLoan(
            'returned',
            now()->subDay()
        );

        $this->artisan('loans:mark-overdue')
            ->assertExitCode(0);

        $this->assertNotNull($overdue->fresh()->overdue_at);

        $this->assertNull($notDue->fresh()->overdue_at);
        $this->assertNull($pending->fresh()->overdue_at);
        $this->assertNull($returned->fresh()->overdue_at);

        // Không thay đổi status của phiếu
        $this->assertSame(
            'borrowed',
            $overdue->fresh()->status
        );
    }

    public function test_command_does_not_mark_same_loan_twice(): void
    {
        $loan = $this->createLoan(
            'borrowed',
            now()->subDays(2)
        );

        // Chạy Command lần thứ nhất
        $this->artisan('loans:mark-overdue')
            ->assertExitCode(0);

        $firstMarkedAt = $loan->fresh()
            ->overdue_at
            ->toDateTimeString();

        // Giả lập thời gian trôi qua một giờ
        $this->travel(1)->hour();

        // Chạy lại Command
        $this->artisan('loans:mark-overdue')
            ->assertExitCode(0);

        // Thời điểm đánh dấu không được thay đổi
        $this->assertSame(
            $firstMarkedAt,
            $loan->fresh()->overdue_at->toDateTimeString()
        );
    }

    public function test_command_does_not_mark_loan_due_now(): void
    {
        $loan = $this->createLoan(
            'borrowed',
            now()
        );

        $this->artisan('loans:mark-overdue')
            ->assertExitCode(0);

        // Đúng thời điểm hết hạn chưa bị coi là quá hạn
        $this->assertNull($loan->fresh()->overdue_at);
    }

    public function test_command_marks_loan_after_due_date(): void
    {
        $loan = $this->createLoan(
            'borrowed',
            now()->addMinute()
        );

        // Lần đầu: chưa quá hạn
        $this->artisan('loans:mark-overdue')
            ->assertExitCode(0);

        $this->assertNull($loan->fresh()->overdue_at);

        // Hai phút sau: đã quá hạn
        $this->travel(2)->minutes();

        $this->artisan('loans:mark-overdue')
            ->assertExitCode(0);

        $this->assertNotNull($loan->fresh()->overdue_at);
    }
}
