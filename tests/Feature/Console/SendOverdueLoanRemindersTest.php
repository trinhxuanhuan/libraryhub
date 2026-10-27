<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Events\LoanOverdueReminderRequested;
use App\Listeners\SendOverdueLoanReminder;
use App\Mail\OverdueLoanReminder;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class SendOverdueLoanRemindersTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        // Chạy Queue đồng bộ trong các test thông thường
        config()->set('queue.default', 'sync');

        // Cố định thời gian kiểm thử
        $this->travelTo(
            Carbon::parse('2026-10-25 12:00:00')
        );

        // Không gửi email thật
        Mail::fake();

        $this->member = User::factory()->create([
            'role' => UserRole::Member->value,
            'is_active' => true,
        ]);
    }

    private function createLoan(
        User $user,
        string $status,
        Carbon $dueAt
    ): Loan {
        return Loan::create([
            'user_id' => $user->id,
            'status' => $status,
            'due_at' => $dueAt,
        ]);
    }

    public function test_command_sends_reminder_for_overdue_loan(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->subDay()
        );

        $this->artisan('loans:send-overdue-reminders')
            ->assertExitCode(0);

        Mail::assertSent(
            OverdueLoanReminder::class,
            function (OverdueLoanReminder $mail) use ($loan) {
                return $mail->loan->id === $loan->id
                    && $mail->hasTo($this->member->email);
            }
        );

        $this->assertNotNull(
            $loan->fresh()->overdue_reminder_sent_at
        );
    }

    public function test_command_skips_loans_that_are_not_eligible(): void
    {
        $future = $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDay()
        );

        $pending = $this->createLoan(
            $this->member,
            'pending',
            now()->subDay()
        );

        $returned = $this->createLoan(
            $this->member,
            'returned',
            now()->subDay()
        );

        $this->artisan('loans:send-overdue-reminders')
            ->assertExitCode(0);

        Mail::assertNothingSent();

        $this->assertNull(
            $future->fresh()->overdue_reminder_sent_at
        );

        $this->assertNull(
            $pending->fresh()->overdue_reminder_sent_at
        );

        $this->assertNull(
            $returned->fresh()->overdue_reminder_sent_at
        );
    }

    public function test_command_does_not_send_same_reminder_twice(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->subDays(2)
        );

        $this->artisan('loans:send-overdue-reminders')
            ->assertExitCode(0);

        $firstSentAt = $loan->fresh()
            ->overdue_reminder_sent_at
            ->toDateTimeString();

        $this->travel(1)->day();

        $this->artisan('loans:send-overdue-reminders')
            ->assertExitCode(0);

        Mail::assertSentTimes(
            OverdueLoanReminder::class,
            1
        );

        $this->assertSame(
            $firstSentAt,
            $loan->fresh()
                ->overdue_reminder_sent_at
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
            now()->subDay()
        );

        $this->createLoan(
            $inactiveMember,
            'borrowed',
            now()->subDay()
        );

        $this->artisan('loans:send-overdue-reminders')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_failed_mail_does_not_mark_reminder_as_sent(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->subDay()
        );

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(
                new RuntimeException('Simulated mail failure')
            );

        $this->artisan('loans:send-overdue-reminders')
            ->assertExitCode(1);

        $this->assertNull(
            $loan->fresh()->overdue_reminder_sent_at
        );
    }

    public function test_reminder_email_template_can_render(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->subDay()
        );

        $mail = new OverdueLoanReminder(
            $loan->load(['user', 'items.book'])
        );

        $html = $mail->render();

        $this->assertStringContainsString(
            'Thông báo sách mượn quá hạn',
            $html
        );
    }

    public function test_command_dispatches_expected_event(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->subDay()
        );

        // Chặn Event để kiểm tra riêng Command
        Event::fake([
            LoanOverdueReminderRequested::class,
        ]);

        $this->artisan('loans:send-overdue-reminders')
            ->assertExitCode(0);

        Event::assertDispatched(
            LoanOverdueReminderRequested::class,
            function (LoanOverdueReminderRequested $event) use ($loan) {
                return $event->loanId === $loan->id;
            }
        );

        Mail::assertNothingSent();

        $this->assertNull(
            $loan->fresh()->overdue_reminder_sent_at
        );
    }

    public function test_listener_sends_mail_when_event_is_dispatched(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->subDay()
        );

        LoanOverdueReminderRequested::dispatch($loan->id);

        Mail::assertSentTimes(
            OverdueLoanReminder::class,
            1
        );

        $this->assertNotNull(
            $loan->fresh()->overdue_reminder_sent_at
        );
    }

    public function test_command_pushes_listener_to_queue(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->subDay()
        );

        // Chặn Queue, không thực thi Job
        Queue::fake();

        $this->artisan('loans:send-overdue-reminders')
            ->assertExitCode(0);

        Queue::assertPushed(
            CallQueuedListener::class,
            function (CallQueuedListener $job) use ($loan) {
                return $job->class === SendOverdueLoanReminder::class
                    && ($job->data[0] ?? null)
                        instanceof LoanOverdueReminderRequested
                    && $job->data[0]->loanId === $loan->id;
            }
        );

        // Job chưa chạy nên chưa có email
        Mail::assertNothingSent();

        // Chưa gửi email thì chưa được đánh dấu
        $this->assertNull(
            $loan->fresh()->overdue_reminder_sent_at
        );
    }

    public function test_listener_rechecks_loan_before_sending(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->subDay()
        );

        // Giả sử phiếu đã được trả trước khi Worker xử lý
        $loan->update([
            'status' => 'returned',
        ]);

        LoanOverdueReminderRequested::dispatch($loan->id);

        Mail::assertNothingSent();

        $this->assertNull(
            $loan->fresh()->overdue_reminder_sent_at
        );
    }

    public function test_listener_has_retry_configuration(): void
    {
        $listener = app(SendOverdueLoanReminder::class);

        $this->assertSame(3, $listener->tries);

        $this->assertSame(60, $listener->backoff);
    }
}
