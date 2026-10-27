<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Events\LoanDueSoonReminderRequested;
use App\Listeners\SendDueSoonLoanReminder;
use App\Mail\DueSoonLoanReminder;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
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
        // Cho phép các test kiểm tra việc gửi mail chạy đồng bộ
        config()->set('queue.default', 'sync');

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

        // Gọi trực tiếp Listener để kiểm tra lỗi gửi mail
        try {
            app(SendDueSoonLoanReminder::class)->handle(
                new LoanDueSoonReminderRequested($loan->id)
            );

            $this->fail('Expected mail sending to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated mail failure',
                $exception->getMessage()
            );
        }

        // Gửi thất bại thì vẫn phải là NULL
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

    public function test_command_pushes_listener_to_queue(): void
    {
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDay()
        );

        // Giả lập Queue, không thực thi Job
        Queue::fake();

        $this->artisan('loans:send-due-soon-reminders')
            ->assertExitCode(0);

        // Command phải tạo đúng Job cho Listener
        Queue::assertPushed(
            CallQueuedListener::class,
            function (CallQueuedListener $job) use ($loan) {
                return $job->class === SendDueSoonLoanReminder::class
                    && ($job->data[0] ?? null)
                        instanceof LoanDueSoonReminderRequested
                    && $job->data[0]->loanId === $loan->id;
            }
        );

        // Job chưa chạy thì email chưa được gửi
        Mail::assertNothingSent();

        // Chưa gửi thành công thì chưa được đánh dấu
        $this->assertNull(
            $loan->fresh()->due_soon_reminder_sent_at
        );
    }

    public function test_queued_listener_is_recorded_as_failed_after_max_attempts(): void
    {
        // Sử dụng Database Queue thật trong môi trường test
        config()->set('queue.default', 'database');
        config()->set('queue.failed.driver', 'database-uuids');

        // Tạo phiếu mượn sắp đến hạn
        $loan = $this->createLoan(
            $this->member,
            'borrowed',
            now()->addDay()
        );

        // Giả lập cả 3 lần gửi email đều thất bại
        Mail::shouldReceive('to')
            ->times(3)
            ->andThrow(
                new RuntimeException('Simulated permanent mail failure')
            );

        // Phát Event, Laravel đưa Listener vào Queue
        event(new LoanDueSoonReminderRequested($loan->id));

        $this->assertDatabaseCount('jobs', 1);

        // Cho Worker xử lý tối đa 3 lần
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            Artisan::call('queue:work', [
                'connection' => 'database',
                '--queue' => 'default',
                '--once' => true,
                '--sleep' => 0,
            ]);

            if ($attempt < 3) {
                // Chưa hết lượt thử nên Job vẫn còn trong Queue
                $this->assertDatabaseCount('jobs', 1);

                // Job chưa thất bại hoàn toàn
                $this->assertDatabaseCount('failed_jobs', 0);

                // Giả lập thời gian trôi qua 61 giây
                $this->travel(61)->seconds();
            }
        }

        // Sau lần thứ 3, Job không còn trong hàng đợi
        $this->assertDatabaseCount('jobs', 0);

        // Job thất bại hoàn toàn được ghi nhận
        $this->assertDatabaseHas('failed_jobs', [
            'queue' => 'default',
        ]);

        // Không được đánh dấu đã gửi email
        $this->assertNull(
            $loan->fresh()->due_soon_reminder_sent_at
        );
    }
}
