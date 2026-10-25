<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Mail\OverdueLoanReminder;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class SendOverdueLoanRemindersTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-25 12:00:00'));

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

        $this->assertNull($future->fresh()->overdue_reminder_sent_at);
        $this->assertNull($pending->fresh()->overdue_reminder_sent_at);
        $this->assertNull($returned->fresh()->overdue_reminder_sent_at);
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

        Mail::assertSentTimes(OverdueLoanReminder::class, 1);

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
            ->andThrow(new RuntimeException('Simulated mail failure'));

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
}
