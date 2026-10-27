<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Events\LoanDueSoonReminderRequested;
use App\Mail\DueSoonLoanReminder;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class SendDueSoonLoanRemindersTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        // Cố định thời gian kiểm thử theo giờ Việt Nam
        $this->travelTo(
            Carbon::parse(
                '2026-10-27 08:00:00',
                'Asia/Ho_Chi_Minh'
            )
        );

        // Không gửi email thật trong quá trình test
        Mail::fake();

        $this->member = User::factory()->create([
            'role' => UserRole::Member->value,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Thể loại kiểm thử',
        ]);

        $this->book = Book::create([
            'category_id' => $category->id,
            'isbn' => '9780000000001',
            'title' => 'Sách kiểm thử nhắc trả',
            'description' => 'Đầu sách dùng để kiểm thử email nhắc trả.',
            'publisher' => 'Nhà xuất bản kiểm thử',
            'published_year' => 2024,
            'total_copies' => 1,
            'available_copies' => 1,
        ]);
    }

    private function createLoan(
        User $user,
        string $status,
        Carbon $dueAt,
        bool $withUnresolvedItem = true
    ): Loan {
        $loan = Loan::create([
            'user_id' => $user->id,
            'status' => $status,
            'due_at' => $dueAt,
        ]);

        if ($withUnresolvedItem) {
            $loan->items()->create([
                'book_id' => $this->book->id,
                'book_copy_id' => null,
            ]);
        }

        return $loan;
    }

    public function test_command_sends_reminder_for_due_soon_loan(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDays(2)
        );

        $this->artisan('loans:send-due-soon-reminders')
            ->assertExitCode(0);

        Mail::assertSent(
            DueSoonLoanReminder::class,
            function (DueSoonLoanReminder $mail) use ($loan) {
                return $mail->loan->id === $loan->id
                    && $mail->hasTo($this->member->email);
            }
        );

        $this->assertNotNull(
            $loan->fresh()->due_soon_reminder_sent_at
        );
    }

    public function test_reminder_uses_calendar_days_in_vietnam(): void
    {
        // Đã là ngày 29/10 tại Việt Nam nhưng vẫn trước hạn.
        // Khoảng nhắc gồm toàn bộ ngày thứ hai tính từ hôm nay.
        $dueAt = Carbon::parse(
            '2026-10-29 23:30:00',
            'Asia/Ho_Chi_Minh'
        )->setTimezone(config('app.timezone', 'UTC'));

        $this->createLoan(
            $this->member,
            'borrowed',
            $dueAt
        );

        $this->artisan('loans:send-due-soon-reminders')
            ->assertExitCode(0);

        Mail::assertSentTimes(DueSoonLoanReminder::class, 1);
    }

    public function test_command_skips_loans_that_are_not_eligible(): void
    {
        $tooEarly = $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDays(4)
        );

        $pending = $this->createLoan(
            $this->member,
            'pending',
            now()->addDay()
        );

        $returned = $this->createLoan(
            $this->member,
            'returned',
            now()->addDay()
        );

        $overdue = $this->createLoan(
            $this->member,
            'borrowed',
            now()->subDay()
        );

        $this->artisan('loans:send-due-soon-reminders')
            ->assertExitCode(0);

        Mail::assertNothingSent();

        foreach ([$tooEarly, $pending, $returned, $overdue] as $loan) {
            $this->assertNull(
                $loan->fresh()->due_soon_reminder_sent_at
            );
        }
    }

    public function test_command_does_not_send_same_reminder_twice(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDays(2)
        );

        $this->artisan('loans:send-due-soon-reminders')
            ->assertExitCode(0);

        $firstSentAt = $loan->fresh()
            ->due_soon_reminder_sent_at
            ->toDateTimeString();

        $this->travel(1)->day();

        $this->artisan('loans:send-due-soon-reminders')
            ->assertExitCode(0);

        Mail::assertSentTimes(DueSoonLoanReminder::class, 1);

        $this->assertSame(
            $firstSentAt,
            $loan->fresh()
                ->due_soon_reminder_sent_at
                ->toDateTimeString()
        );
    }

    public function test_command_skips_staff_and_inactive_members(): void
    {
        $librarian = User::factory()->create([
            'role' => UserRole::Librarian->value,
            'is_active' => true,
        ]);

        $inactiveMember = User::factory()->create([
            'role' => UserRole::Member->value,
            'is_active' => false,
        ]);

        $this->createLoan(
            $librarian,
            'borrowed',
            now()->addDay()
        );

        $this->createLoan(
            $inactiveMember,
            'borrowed',
            now()->addDay()
        );

        $this->artisan('loans:send-due-soon-reminders')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_command_skips_loan_without_unresolved_items(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDay(),
            false
        );

        $this->artisan('loans:send-due-soon-reminders')
            ->assertExitCode(0);

        Mail::assertNothingSent();

        $this->assertNull(
            $loan->fresh()->due_soon_reminder_sent_at
        );
    }

    public function test_failed_mail_does_not_mark_reminder_as_sent(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDay()
        );

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(
                new RuntimeException('Simulated mail failure')
            );

        $this->artisan('loans:send-due-soon-reminders')
            ->assertExitCode(1);

        $this->assertNull(
            $loan->fresh()->due_soon_reminder_sent_at
        );
    }

    public function test_command_dispatches_expected_event(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDay()
        );

        // Chặn Listener để chỉ kiểm tra Command phát Event
        Event::fake([
            LoanDueSoonReminderRequested::class,
        ]);

        $this->artisan('loans:send-due-soon-reminders')
            ->assertExitCode(0);

        Event::assertDispatched(
            LoanDueSoonReminderRequested::class,
            function (LoanDueSoonReminderRequested $event) use ($loan) {
                return $event->loanId === $loan->id;
            }
        );

        // Listener không chạy khi Event bị fake
        Mail::assertNothingSent();

        $this->assertNull(
            $loan->fresh()->due_soon_reminder_sent_at
        );
    }

    public function test_listener_sends_mail_when_event_is_dispatched(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDay()
        );

        LoanDueSoonReminderRequested::dispatch($loan->id);

        Mail::assertSentTimes(DueSoonLoanReminder::class, 1);

        $this->assertNotNull(
            $loan->fresh()->due_soon_reminder_sent_at
        );
    }

    public function test_reminder_days_can_be_changed_through_config(): void
    {
        config()->set(
            'library_loan.due_soon_reminder_days',
            3
        );

        $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDays(3)
        );

        $this->artisan('loans:send-due-soon-reminders')
            ->assertExitCode(0);

        Mail::assertSentTimes(DueSoonLoanReminder::class, 1);
    }

    public function test_reminder_email_template_can_render(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDay()
        );

        $mail = new DueSoonLoanReminder(
            $loan->load(['user', 'items.book'])
        );

        $html = $mail->render();

        $this->assertStringContainsString(
            'Nhắc nhở sắp đến hạn trả sách',
            $html
        );

        $this->assertStringContainsString(
            'Sách kiểm thử nhắc trả',
            $html
        );
    }
}
