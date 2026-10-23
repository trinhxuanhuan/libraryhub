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

class BookSelectionTest extends TestCase
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

        // Giả lập Member đã đăng nhập
        $this->withSession([
            'manual_auth_user_id' => $this->member->id,
        ]);

        $this->category = Category::create([
            'name' => 'Lập trình',
            'description' => 'Sách lập trình',
        ]);
    }

    private function selectionKey(): string
    {
        return 'loan_selected_books_'.$this->member->id;
    }

    private function createBook(int $availableCopies = 1): Book
    {
        $this->bookSequence++;

        $book = Book::create([
            'title' => 'Sách kiểm thử '.$this->bookSequence,
            'isbn' => sprintf('978%010d', $this->bookSequence),
            'category_id' => $this->category->id,
            'description' => 'Kiểm thử danh sách mượn',
            'publisher' => 'NXB Test',
            'published_year' => 2024,
            'total_copies' => 1,
            'available_copies' => $availableCopies,
        ]);

        $book->copies()->create([
            'barcode' => sprintf('TEST-%06d-0001', $book->id),
            'status' => $availableCopies > 0
                ? BookCopyStatus::Available
                : BookCopyStatus::Unverified,
        ]);

        return $book;
    }

    public function test_member_can_add_book_to_selection(): void
    {
        $book = $this->createBook();

        $this->post(route('member.selection.add', $book))
            ->assertRedirect(route('member.selection.index'))
            ->assertSessionHas($this->selectionKey(), [$book->id]);

        // Chọn sách chưa tạo phiếu mượn
        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
    }

    public function test_member_cannot_add_duplicate_book(): void
    {
        $book = $this->createBook();

        $this->withSession([
            $this->selectionKey() => [$book->id],
        ]);

        $this->post(route('member.selection.add', $book))
            ->assertRedirect(route('member.selection.index'))
            ->assertSessionHas($this->selectionKey(), [$book->id]);
    }

    public function test_member_can_view_selected_books(): void
    {
        $book1 = $this->createBook();
        $book2 = $this->createBook();

        $this->withSession([
            $this->selectionKey() => [$book1->id, $book2->id],
        ]);

        $this->get(route('member.selection.index'))
            ->assertOk()
            ->assertSeeText($book1->title)
            ->assertSeeText($book2->title);
    }

    public function test_member_can_remove_selected_book(): void
    {
        $book1 = $this->createBook();
        $book2 = $this->createBook();

        $this->withSession([
            $this->selectionKey() => [$book1->id, $book2->id],
        ]);

        $this->delete(route('member.selection.remove', $book1->id))
            ->assertRedirect(route('member.selection.index'))
            ->assertSessionHas($this->selectionKey(), [$book2->id]);
    }

    public function test_member_cannot_select_unavailable_book(): void
    {
        $book = $this->createBook(0);

        $this->post(route('member.selection.add', $book))
            ->assertSessionHasErrors('book_ids')
            ->assertSessionMissing($this->selectionKey());

        $this->assertDatabaseCount('loans', 0);
    }

    public function test_member_can_submit_selected_books(): void
    {
        $book1 = $this->createBook();
        $book2 = $this->createBook();

        $this->withSession([
            $this->selectionKey() => [$book1->id, $book2->id],
        ]);

        $this->post(route('member.selection.submit'))
            ->assertRedirect(route('member.loans.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionMissing($this->selectionKey());

        $this->assertDatabaseCount('loans', 1);
        $this->assertDatabaseCount('loan_items', 2);

        $loan = Loan::with('items')->firstOrFail();

        $this->assertSame('pending', $loan->status);
        $this->assertSame($this->member->id, $loan->user_id);

        $this->assertEqualsCanonicalizing(
            [$book1->id, $book2->id],
            $loan->items->pluck('book_id')->all()
        );

        foreach ($loan->items as $item) {
            $this->assertNull($item->book_copy_id);
        }

        // Chưa giao sách, số lượng khả dụng không thay đổi
        $this->assertSame(
            1,
            (int) $book1->fresh()->available_copies
        );
    }

    public function test_member_cannot_submit_empty_selection(): void
    {
        $this->post(route('member.selection.submit'))
            ->assertSessionHasErrors('book_ids');

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
    }
}
