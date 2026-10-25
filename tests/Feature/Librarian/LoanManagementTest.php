<?php

namespace Tests\Feature\Librarian;

use App\Enums\BookCopyStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $librarian;

    private User $member;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->librarian = User::factory()->create();
        $this->librarian->forceFill([
            'role' => UserRole::Librarian,
            'is_active' => true,
        ])->save();

        $this->member = User::factory()->create();
        $this->member->forceFill([
            'role' => UserRole::Member,
            'is_active' => true,
        ])->save();

        $this->withSession([
            'manual_auth_user_id' => $this->librarian->id,
        ]);

        $this->category = Category::create([
            'name' => 'Thể loại kiểm thử',
            'description' => 'Dữ liệu dùng cho Feature Test',
        ]);
    }

    private function createBookWithCopy(): array
    {
        $book = Book::factory()->create([
            'category_id' => $this->category->id,
            'cover_path' => null,
            'total_copies' => 1,
            'available_copies' => 1,
        ]);

        $copy = $book->copies()->create([
            'barcode' => sprintf(
                'TEST-%06d-0001',
                $book->id
            ),
            'status' => BookCopyStatus::Available->value,
        ]);

        return [$book, $copy];
    }

    private function createPendingLoan(Book ...$books): Loan
    {
        $loan = Loan::create([
            'user_id' => $this->member->id,
            'status' => 'pending',
        ]);

        foreach ($books as $book) {
            $loan->items()->create([
                'book_id' => $book->id,
                'book_copy_id' => null,
            ]);
        }

        return $loan;
    }

    public function test_librarian_can_view_loan_details(): void
    {
        [$book] = $this->createBookWithCopy();

        $loan = $this->createPendingLoan($book);

        $this->get(route('librarian.loans.index'))
            ->assertOk();

        $this->get(route('librarian.loans.show', $loan))
            ->assertOk()
            ->assertSeeText($book->title)
            ->assertSeeText($this->member->name);
    }

    public function test_librarian_can_issue_multiple_books(): void
    {
        [$book1, $copy1] = $this->createBookWithCopy();
        [$book2, $copy2] = $this->createBookWithCopy();

        $loan = $this->createPendingLoan($book1, $book2);

        $items = $loan->items()->orderBy('id')->get();

        $this->post(
            route('librarian.loans.issue', $loan),
            [
                'copies' => [
                    $items[0]->id => $copy1->id,
                    $items[1]->id => $copy2->id,
                ],
            ]
        )
            ->assertRedirect(route('librarian.loans.show', $loan))
            ->assertSessionHasNoErrors();

        $loan->refresh();

        $this->assertSame('borrowed', $loan->status);
        $this->assertSame(
            $this->librarian->id,
            $loan->issued_by
        );

        $this->assertNotNull($loan->borrowed_at);
        $this->assertNotNull($loan->due_at);

        $this->assertDatabaseHas('loan_items', [
            'id' => $items[0]->id,
            'book_copy_id' => $copy1->id,
        ]);

        $this->assertDatabaseHas('loan_items', [
            'id' => $items[1]->id,
            'book_copy_id' => $copy2->id,
        ]);

        $this->assertDatabaseHas('book_copies', [
            'id' => $copy1->id,
            'status' => BookCopyStatus::Borrowed->value,
        ]);

        $this->assertDatabaseHas('book_copies', [
            'id' => $copy2->id,
            'status' => BookCopyStatus::Borrowed->value,
        ]);

        $this->assertSame(
            0,
            (int) $book1->fresh()->available_copies
        );

        $this->assertSame(
            0,
            (int) $book2->fresh()->available_copies
        );
    }

    public function test_librarian_can_reject_pending_loan(): void
    {
        [$book, $copy] = $this->createBookWithCopy();

        $loan = $this->createPendingLoan($book);

        $this->post(route('librarian.loans.reject', $loan))
            ->assertRedirect(route('librarian.loans.show', $loan))
            ->assertSessionHasNoErrors();

        $this->assertSame('rejected', $loan->fresh()->status);

        $this->assertSame(
            1,
            (int) $book->fresh()->available_copies
        );

        $this->assertSame(
            BookCopyStatus::Available->value,
            $copy->fresh()->getRawOriginal('status')
        );
    }

    public function test_returning_books_updates_loan_status(): void
    {
        [$book1, $copy1] = $this->createBookWithCopy();
        [$book2, $copy2] = $this->createBookWithCopy();

        $loan = $this->createPendingLoan($book1, $book2);

        $items = $loan->items()->orderBy('id')->get();

        $this->post(
            route('librarian.loans.issue', $loan),
            [
                'copies' => [
                    $items[0]->id => $copy1->id,
                    $items[1]->id => $copy2->id,
                ],
            ]
        )->assertSessionHasNoErrors();

        // Trả bản sách thứ nhất
        $this->post(route(
            'librarian.loans.items.return',
            [$loan, $items[0]]
        ))->assertSessionHasNoErrors();

        // Loan vẫn borrowed vì còn một bản chưa trả
        $this->assertSame('borrowed', $loan->fresh()->status);

        $this->assertNotNull($items[0]->fresh()->returned_at);
        $this->assertNull($items[1]->fresh()->returned_at);

        $this->assertSame(
            1,
            (int) $book1->fresh()->available_copies
        );

        $this->assertSame(
            0,
            (int) $book2->fresh()->available_copies
        );

        // Trả bản sách thứ hai
        $this->post(route(
            'librarian.loans.items.return',
            [$loan, $items[1]]
        ))->assertSessionHasNoErrors();

        // Trả đủ sách thì Loan mới hoàn thành
        $this->assertSame('returned', $loan->fresh()->status);
        $this->assertNotNull($loan->fresh()->returned_at);

        $this->assertSame(
            1,
            (int) $book2->fresh()->available_copies
        );

        $this->assertSame(
            BookCopyStatus::Available->value,
            $copy1->fresh()->getRawOriginal('status')
        );

        $this->assertSame(
            BookCopyStatus::Available->value,
            $copy2->fresh()->getRawOriginal('status')
        );
    }

    public function test_librarian_cannot_issue_same_loan_twice(): void
    {
        [$book, $copy] = $this->createBookWithCopy();

        $loan = $this->createPendingLoan($book);
        $item = $loan->items()->firstOrFail();

        $data = [
            'copies' => [
                $item->id => $copy->id,
            ],
        ];

        // Lần giao thứ nhất thành công
        $this->post(route('librarian.loans.issue', $loan), $data)
            ->assertSessionHasNoErrors();

        // Không được giao lại phiếu đang borrowed
        $this->post(route('librarian.loans.issue', $loan), $data)
            ->assertSessionHasErrors('loan');

        $this->assertSame('borrowed', $loan->fresh()->status);

        $this->assertSame(
            0,
            (int) $book->fresh()->available_copies
        );
    }

    public function test_librarian_cannot_issue_wrong_book_copy(): void
    {
        [$book1] = $this->createBookWithCopy();
        [$book2, $copy2] = $this->createBookWithCopy();

        $loan = $this->createPendingLoan($book1);
        $item = $loan->items()->firstOrFail();

        // Cố giao bản vật lý của Book 2 cho Book 1
        $this->post(
            route('librarian.loans.issue', $loan),
            [
                'copies' => [
                    $item->id => $copy2->id,
                ],
            ]
        )->assertSessionHasErrors('copies');

        $this->assertSame('pending', $loan->fresh()->status);

        $this->assertNull($item->fresh()->book_copy_id);

        $this->assertSame(
            1,
            (int) $book1->fresh()->available_copies
        );

        $this->assertSame(
            1,
            (int) $book2->fresh()->available_copies
        );
    }

    public function test_librarian_cannot_return_same_item_twice(): void
    {
        [$book, $copy] = $this->createBookWithCopy();

        $loan = $this->createPendingLoan($book);
        $item = $loan->items()->firstOrFail();

        $this->post(
            route('librarian.loans.issue', $loan),
            [
                'copies' => [
                    $item->id => $copy->id,
                ],
            ]
        )->assertSessionHasNoErrors();

        // Lần trả thứ nhất
        $this->post(route(
            'librarian.loans.items.return',
            [$loan, $item]
        ))->assertSessionHasNoErrors();

        // Lần trả thứ hai phải bị từ chối
        $this->post(route(
            'librarian.loans.items.return',
            [$loan, $item]
        ))->assertSessionHasErrors('loan');

        $this->assertSame('returned', $loan->fresh()->status);

        // Không được tăng số lượng sách lên 2
        $this->assertSame(
            1,
            (int) $book->fresh()->available_copies
        );
    }

    public function test_member_cannot_access_librarian_routes(): void
    {
        $this->withSession([
            'manual_auth_user_id' => $this->member->id,
        ]);

        $this->get(route('librarian.loans.index'))
            ->assertForbidden();
    }
}
