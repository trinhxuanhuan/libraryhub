<?php

namespace Tests\Feature\Member;

use App\Enums\BookCopyStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanRenewalTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private User $otherMember;

    private Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = User::factory()->create();
        $this->member->forceFill([
            'role' => UserRole::Member,
            'is_active' => true,
        ])->save();

        $this->otherMember = User::factory()->create();
        $this->otherMember->forceFill([
            'role' => UserRole::Member,
            'is_active' => true,
        ])->save();

        $this->withSession([
            'manual_auth_user_id' => $this->member->id,
        ]);

        $category = Category::create([
            'name' => 'Gia hạn sách',
            'description' => 'Thể loại kiểm thử',
        ]);

        $book = Book::factory()->create([
            'category_id' => $category->id,
            'cover_path' => null,
            'total_copies' => 1,
            'available_copies' => 0,
        ]);

        $copy = $book->copies()->create([
            'barcode' => 'RENEW-0001',
            'status' => BookCopyStatus::Borrowed->value,
        ]);

        $this->loan = Loan::create([
            'user_id' => $this->member->id,
            'status' => 'borrowed',
            'borrowed_at' => now()->subDays(2),
            'due_at' => now()->addDays(3)->startOfSecond(),
            'renewal_count' => 0,
        ]);

        $this->loan->items()->create([
            'book_id' => $book->id,
            'book_copy_id' => $copy->id,
        ]);
    }

    public function test_member_can_renew_eligible_loan(): void
    {
        $oldDueAt = $this->loan->due_at->copy();

        $this->post(route('member.loans.renew', $this->loan))
            ->assertRedirect(route('member.loans.index'))
            ->assertSessionHasNoErrors();

        $loan = $this->loan->fresh();

        $this->assertSame(1, $loan->renewal_count);
        $this->assertSame('borrowed', $loan->status);

        $this->assertSame(
            $oldDueAt->addDays(7)->toDateTimeString(),
            $loan->due_at->toDateTimeString()
        );
    }

    public function test_member_cannot_renew_twice(): void
    {
        $this->post(route('member.loans.renew', $this->loan))
            ->assertSessionHasNoErrors();

        $dueAt = $this->loan->fresh()->due_at->toDateTimeString();

        $this->post(route('member.loans.renew', $this->loan))
            ->assertSessionHasErrors('loan');

        $this->assertSame(1, $this->loan->fresh()->renewal_count);

        $this->assertSame(
            $dueAt,
            $this->loan->fresh()->due_at->toDateTimeString()
        );
    }

    public function test_member_cannot_renew_overdue_loan(): void
    {
        $this->loan->update([
            'due_at' => now()->subDay(),
        ]);

        $this->post(route('member.loans.renew', $this->loan))
            ->assertSessionHasErrors('loan');

        $this->assertSame(0, $this->loan->fresh()->renewal_count);
    }

    public function test_member_cannot_renew_pending_loan(): void
    {
        $this->loan->update([
            'status' => 'pending',
        ]);

        $this->post(route('member.loans.renew', $this->loan))
            ->assertSessionHasErrors('loan');

        $this->assertSame(0, $this->loan->fresh()->renewal_count);
    }

    public function test_member_cannot_renew_another_members_loan(): void
    {
        $this->withSession([
            'manual_auth_user_id' => $this->otherMember->id,
        ]);

        $this->post(route('member.loans.renew', $this->loan))
            ->assertNotFound();

        $this->assertSame(0, $this->loan->fresh()->renewal_count);
    }

    public function test_member_cannot_renew_partially_returned_loan(): void
    {
        $this->loan->items()->firstOrFail()->update([
            'returned_at' => now(),
        ]);

        $this->post(route('member.loans.renew', $this->loan))
            ->assertSessionHasErrors('loan');

        $this->assertSame(0, $this->loan->fresh()->renewal_count);
    }
}
