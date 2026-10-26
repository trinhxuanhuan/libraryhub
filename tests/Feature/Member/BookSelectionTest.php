<?php

namespace Tests\Feature\Member;

use App\Enums\BookCopyStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use App\Services\PasswordHasher;
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

    /**
     * Tạo đầu sách và các bản vật lý phục vụ kiểm thử.
     */
    private function createBook(int $availableCopies = 1): Book
    {
        $this->bookSequence++;

        $totalCopies = max(1, $availableCopies);

        $book = Book::create([
            'title' => 'Sách kiểm thử '.$this->bookSequence,
            'isbn' => sprintf('978%010d', $this->bookSequence),
            'category_id' => $this->category->id,
            'description' => 'Kiểm thử danh sách mượn',
            'publisher' => 'NXB Test',
            'published_year' => 2024,
            'total_copies' => $totalCopies,
            'available_copies' => $availableCopies,
        ]);

        for ($copyNumber = 1; $copyNumber <= $totalCopies; $copyNumber++) {
            $book->copies()->create([
                'barcode' => sprintf(
                    'TEST-%06d-%04d',
                    $book->id,
                    $copyNumber
                ),
                'status' => $copyNumber <= $availableCopies
                    ? BookCopyStatus::Available
                    : BookCopyStatus::Unverified,
            ]);
        }

        return $book;
    }

    public function test_member_can_add_book_to_selection(): void
    {
        $book = $this->createBook();

        $this->post(route('member.selection.add', $book))
            ->assertRedirect(route('member.selection.index'))
            ->assertSessionHasNoErrors();

        // Không truyền quantity thì mặc định là 1
        $this->assertDatabaseHas('book_selections', [
            'user_id' => $this->member->id,
            'book_id' => $book->id,
            'quantity' => 1,
        ]);

        // Chọn sách chưa tạo phiếu mượn
        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
    }

    public function test_member_cannot_add_duplicate_book(): void
    {
        $book = $this->createBook();

        $this->member->selectedBooks()->attach($book->id);

        $this->post(route('member.selection.add', $book))
            ->assertRedirect(route('member.selection.index'))
            ->assertSessionHasNoErrors();

        // Một đầu sách chỉ có một dòng trong danh sách chọn
        $this->assertDatabaseCount('book_selections', 1);

        $this->assertDatabaseHas('book_selections', [
            'user_id' => $this->member->id,
            'book_id' => $book->id,
            'quantity' => 1,
        ]);
    }

    public function test_member_can_select_multiple_copies_of_one_book(): void
    {
        $book = $this->createBook(3);

        $this->post(route('member.selection.add', $book), [
            'quantity' => 3,
        ])
            ->assertRedirect(route('member.selection.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('book_selections', [
            'user_id' => $this->member->id,
            'book_id' => $book->id,
            'quantity' => 3,
        ]);

        // Ba bản của cùng một đầu sách chỉ tạo một dòng lựa chọn
        $this->assertDatabaseCount('book_selections', 1);
    }

    public function test_member_can_update_selected_quantity(): void
    {
        $book = $this->createBook(4);

        // Ban đầu chọn 3 bản
        $this->post(route('member.selection.add', $book), [
            'quantity' => 3,
        ])->assertSessionHasNoErrors();

        // Cập nhật số lượng còn 2 bản
        $this->post(route('member.selection.add', $book), [
            'quantity' => 2,
        ])
            ->assertRedirect(route('member.selection.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('book_selections', [
            'user_id' => $this->member->id,
            'book_id' => $book->id,
            'quantity' => 2,
        ]);

        // Cập nhật không tạo thêm dòng trùng
        $this->assertDatabaseCount('book_selections', 1);
    }

    public function test_member_can_view_selected_books(): void
    {
        $book1 = $this->createBook();
        $book2 = $this->createBook();

        $this->member->selectedBooks()->attach([
            $book1->id,
            $book2->id,
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

        $this->member->selectedBooks()->attach([
            $book1->id,
            $book2->id,
        ]);

        $this->delete(route('member.selection.remove', $book1->id))
            ->assertRedirect(route('member.selection.index'));

        $this->assertDatabaseMissing('book_selections', [
            'user_id' => $this->member->id,
            'book_id' => $book1->id,
        ]);

        $this->assertDatabaseHas('book_selections', [
            'user_id' => $this->member->id,
            'book_id' => $book2->id,
        ]);
    }

    public function test_member_cannot_select_unavailable_book(): void
    {
        $book = $this->createBook(0);

        $this->post(route('member.selection.add', $book))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('book_selections', 0);
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_member_cannot_select_more_copies_than_available(): void
    {
        $book = $this->createBook(2);

        // Chỉ còn 2 bản nhưng yêu cầu 3 bản
        $this->post(route('member.selection.add', $book), [
            'quantity' => 3,
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('book_selections', 0);
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_member_can_submit_selected_books(): void
    {
        $book1 = $this->createBook();
        $book2 = $this->createBook();

        $this->member->selectedBooks()->attach([
            $book1->id,
            $book2->id,
        ]);

        $this->post(route('member.selection.submit'))
            ->assertRedirect(route('member.loans.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('book_selections', 0);

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

    public function test_member_can_submit_multiple_copies_of_books(): void
    {
        $book1 = $this->createBook(3);
        $book2 = $this->createBook(2);

        // Chọn 3 bản của đầu sách thứ nhất
        $this->post(route('member.selection.add', $book1), [
            'quantity' => 3,
        ])->assertSessionHasNoErrors();

        // Chọn 2 bản của đầu sách thứ hai
        $this->post(route('member.selection.add', $book2), [
            'quantity' => 2,
        ])->assertSessionHasNoErrors();

        // Gửi yêu cầu mượn
        $this->post(route('member.selection.submit'))
            ->assertRedirect(route('member.loans.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('loans', 1);
        $this->assertDatabaseCount('loan_items', 5);
        $this->assertDatabaseCount('book_selections', 0);

        $loan = Loan::with('items')->firstOrFail();

        $this->assertSame('pending', $loan->status);
        $this->assertSame($this->member->id, $loan->user_id);

        // Đầu sách thứ nhất có 3 LoanItem
        $this->assertSame(
            3,
            $loan->items->where('book_id', $book1->id)->count()
        );

        // Đầu sách thứ hai có 2 LoanItem
        $this->assertSame(
            2,
            $loan->items->where('book_id', $book2->id)->count()
        );

        // Chưa giao bản sách vật lý nào
        foreach ($loan->items as $item) {
            $this->assertNull($item->book_copy_id);
        }

        // Chưa giao sách nên tồn kho chưa giảm
        $this->assertSame(
            3,
            (int) $book1->fresh()->available_copies
        );

        $this->assertSame(
            2,
            (int) $book2->fresh()->available_copies
        );
    }

    public function test_member_cannot_submit_when_stock_changes_after_selection(): void
    {
        $book = $this->createBook(2);

        // Member chọn 2 bản khi thư viện còn đủ sách
        $this->post(route('member.selection.add', $book), [
            'quantity' => 2,
        ])->assertSessionHasNoErrors();

        // Giả lập số lượng khả dụng thay đổi trước khi gửi
        $book->update([
            'available_copies' => 1,
        ]);

        $this->post(route('member.selection.submit'))
            ->assertSessionHasErrors('book_ids');

        // Không được tạo phiếu khi không đủ số lượng
        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);

        // Giữ danh sách chọn để Member có thể sửa lại
        $this->assertDatabaseHas('book_selections', [
            'user_id' => $this->member->id,
            'book_id' => $book->id,
            'quantity' => 2,
        ]);
    }

    public function test_member_cannot_submit_empty_selection(): void
    {
        $this->post(route('member.selection.submit'))
            ->assertSessionHasErrors('book_ids');

        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('loan_items', 0);
    }

    public function test_selection_persists_after_logout_and_login(): void
    {
        $book = $this->createBook(2);

        $password = 'Password123!';

        $this->member->forceFill([
            'password' => app(PasswordHasher::class)->hash($password),
        ])->save();

        // Member chọn 2 bản sách
        $this->post(route('member.selection.add', $book), [
            'quantity' => 2,
        ])
            ->assertRedirect(route('member.selection.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('book_selections', [
            'user_id' => $this->member->id,
            'book_id' => $book->id,
            'quantity' => 2,
        ]);

        // Đăng xuất
        $this->post(route('logout'))
            ->assertRedirect(route('home'));

        // Đăng nhập lại bằng Authentication thủ công
        $this->post(route('login.store'), [
            'email' => $this->member->email,
            'password' => $password,
        ])->assertRedirect(route('home'));

        // Đầu sách vẫn hiển thị trong danh sách
        $this->get(route('member.selection.index'))
            ->assertOk()
            ->assertSeeText($book->title);

        // Số lượng đã chọn cũng được giữ nguyên
        $this->assertDatabaseHas('book_selections', [
            'user_id' => $this->member->id,
            'book_id' => $book->id,
            'quantity' => 2,
        ]);
    }

    public function test_members_have_separate_book_selections(): void
    {
        $book = $this->createBook();

        // Member A chọn sách
        $this->member->selectedBooks()->attach($book->id);

        // Tạo Member B
        $otherMember = User::factory()->create();

        $otherMember->forceFill([
            'role' => UserRole::Member,
            'is_active' => true,
        ])->save();

        // Giả lập Member B đăng nhập
        $this->withSession([
            'manual_auth_user_id' => $otherMember->id,
        ]);

        // Member B không thấy sách của Member A
        $this->get(route('member.selection.index'))
            ->assertOk()
            ->assertDontSeeText($book->title);

        // Member B không thể xóa lựa chọn của Member A
        $this->delete(route('member.selection.remove', $book->id))
            ->assertRedirect(route('member.selection.index'));

        // Sách của Member A vẫn được giữ nguyên
        $this->assertDatabaseHas('book_selections', [
            'user_id' => $this->member->id,
            'book_id' => $book->id,
        ]);
    }
}
