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

class LoanRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Category $category;

    private int $bookSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = User::factory()->create();

        $this->member->forceFill([
            'role' => UserRole::Member,
            'is_active' => true,
        ])->save();

        $this->withSession([
            'manual_auth_user_id' => $this->member->id,
        ]);

        $this->category = Category::create([
            'name' => 'Lập trình',
            'description' => 'Sách lập trình',
        ]);
    }

    private function createBook(
        int $availableCopies = 1
    ): Book {
        $this->bookSequence++;

        $book = Book::create([
            'title' => 'Sách kiểm thử '.$this->bookSequence,
            'isbn' => sprintf('978%010d', $this->bookSequence),
            'category_id' => $this->category->id,
            'description' => 'Sách dùng để kiểm thử Loan',
            'publisher' => 'NXB Test',
            'published_year' => 2024,
            'total_copies' => 1,
            'available_copies' => $availableCopies,
        ]);

        $book->copies()->create([
            'barcode' => sprintf(
                'TEST-%06d-0001',
                $book->id
            ),
            'status' => $availableCopies > 0
                ? BookCopyStatus::Available
                : BookCopyStatus::Unverified,
        ]);

        return $book;
    }

    public function test_member_can_request_multiple_books(): void
    {
        $book1 = $this->createBook();
        $book2 = $this->createBook();

        $this->post(route('member.loans.store'), [
            'book_ids' => [$book1->id, $book2->id],
        ])
            ->assertRedirect(route('member.loans.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('loans', 1);
        $this->assertDatabaseCount('loan_items', 2);

        $loan = Loan::with('items')->firstOrFail();

        $this->assertSame('pending', $loan->status);
        $this->assertSame($this->member->id, $loan->user_id);

        $this->assertEqualsCanonicalizing(
            [$book1->id, $book2->id],
            $loan->items->pluck('book_id')->all()
        );

        // Chưa giao sách nên chưa gắn BookCopy.
        foreach ($loan->items as $item) {
            $this->assertNull($item->book_copy_id);
        }

        // Gửi yêu cầu không làm giảm số bản khả dụng.
        $this->assertSame(
            1,
            (int) $book1->fresh()->available_copies
        );

        $this->assertSame(
            BookCopyStatus::Available,
            $book1->copies()->firstOrFail()->status
        );
    }

    public function test_member_must_select_at_least_one_book(): void
    {
        $this->post(route('member.loans.store'), [
            'book_ids' => [],
        ])->assertSessionHasErrors('book_ids');

        $this->assertDatabaseCount('loans', 0);
    }

    public function test_member_cannot_select_duplicate_books(): void
    {
        $book = $this->createBook();

        $this->post(route('member.loans.store'), [
            'book_ids' => [$book->id, $book->id],
        ])->assertSessionHasErrors('book_ids.0');

        $this->assertDatabaseCount('loans', 0);
    }

    public function test_member_cannot_request_unavailable_book(): void
    {
        $book = $this->createBook(0);

        $this->post(route('member.loans.store'), [
            'book_ids' => [$book->id],
        ])->assertSessionHasErrors('book_ids');

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
    }

    public function test_member_only_sees_own_loans(): void
    {
        $ownBook = $this->createBook();
        $otherBook = $this->createBook();

        $ownLoan = Loan::create([
            'user_id' => $this->member->id,
            'status' => 'pending',
        ]);

        $ownLoan->items()->create([
            'book_id' => $ownBook->id,
            'book_copy_id' => null,
        ]);

        $otherMember = User::factory()->create();

        $otherLoan = Loan::create([
            'user_id' => $otherMember->id,
            'status' => 'pending',
        ]);

        $otherLoan->items()->create([
            'book_id' => $otherBook->id,
            'book_copy_id' => null,
        ]);

        $this->get(route('member.loans.index'))
            ->assertOk()
            ->assertSeeText($ownBook->title)
            ->assertDontSeeText($otherBook->title);
    }
}
