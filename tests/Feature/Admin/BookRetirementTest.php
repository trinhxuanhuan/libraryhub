<?php

namespace Tests\Feature\Admin;

use App\Enums\BookCopyStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use App\Services\BookRetirementService;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookRetirementTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private int $bookSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Lập trình',
            'description' => 'Sách lập trình',
        ]);
    }

    // Tạo tài khoản phục vụ kiểm thử
    private function createUser(
        UserRole $role = UserRole::Member
    ): User {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'is_active' => true,
        ])->save();

        return $user;
    }

    // Tạo đầu sách và các bản sách vật lý
    private function createBook(int $availableCopies = 1): Book
    {
        $this->bookSequence++;

        $book = Book::create([
            'title' => 'Sách kiểm thử '.$this->bookSequence,
            'isbn' => sprintf('978%010d', $this->bookSequence),
            'category_id' => $this->category->id,
            'description' => 'Kiểm thử ngừng cung cấp sách',
            'publisher' => 'NXB Test',
            'published_year' => 2024,
            'total_copies' => $availableCopies,
            'available_copies' => $availableCopies,
        ]);

        for ($i = 1; $i <= $availableCopies; $i++) {
            $book->copies()->create([
                'barcode' => sprintf(
                    'RETIRE-%06d-%04d',
                    $book->id,
                    $i
                ),
                'status' => BookCopyStatus::Available,
            ]);
        }

        return $book;
    }

    // 1. Ngừng cung cấp sách có phiếu đang chờ duyệt
    public function test_retiring_book_rejects_pending_loan(): void
    {
        $member = $this->createUser();
        $book = $this->createBook();

        $loan = app(LoanService::class)->requestLoan(
            $member->id,
            [$book->id]
        );

        $affectedCount = app(BookRetirementService::class)
            ->retire($book);

        // Có một phiếu bị từ chối
        $this->assertSame(1, $affectedCount);

        // Đầu sách được soft delete
        $this->assertSoftDeleted($book);

        // Phiếu chuyển sang trạng thái rejected và lưu lý do
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'rejected',
            'rejection_reason' => __(
                'message.loans.rejected_book_retired',
                ['book' => $book->title]
            ),
        ]);

        // Lịch sử vẫn truy xuất được thông tin đầu sách
        $this->assertSame(
            $book->title,
            $loan->items()->firstOrFail()->book?->title
        );
    }

    // 2. Ngừng cung cấp sách không ảnh hưởng phiếu đã giao
    public function test_retiring_book_does_not_interrupt_borrowed_loan(): void
    {
        $member = $this->createUser();

        $librarian = $this->createUser(
            UserRole::Librarian
        );

        $book = $this->createBook();

        $copy = $book->copies()->firstOrFail();

        // Member gửi yêu cầu
        $loan = app(LoanService::class)->requestLoan(
            $member->id,
            [$book->id]
        );

        $item = $loan->items()->firstOrFail();

        // Thủ thư duyệt và giao bản sách
        app(LoanService::class)->issueLoan(
            $loan->id,
            [$item->id => $copy->id],
            $librarian->id
        );

        $this->assertSame(
            'borrowed',
            $loan->fresh()->status
        );

        // Admin ngừng cung cấp đầu sách
        $affectedCount = app(BookRetirementService::class)
            ->retire($book);

        // Phiếu đã giao không bị từ chối
        $this->assertSame(0, $affectedCount);

        $this->assertSame(
            'borrowed',
            $loan->fresh()->status
        );

        $this->assertSoftDeleted($book);

        // Member vẫn có thể trả sách
        app(LoanService::class)->returnItem(
            $loan->id,
            $item->id,
            'normal'
        );

        $this->assertSame(
            'returned',
            $loan->fresh()->status
        );
    }

    // 3. Loại bỏ sách ngừng cung cấp khỏi danh sách chọn tạm
    public function test_retiring_book_removes_it_from_member_selection(): void
    {
        $member = $this->createUser();

        $retiredBook = $this->createBook();
        $otherBook = $this->createBook();

        // Member chọn hai đầu sách
        $member->selectedBooks()->attach([
            $retiredBook->id => ['quantity' => 1],
            $otherBook->id => ['quantity' => 1],
        ]);

        // Admin ngừng cung cấp đầu sách thứ nhất
        $affectedCount = app(BookRetirementService::class)
            ->retire($retiredBook);

        // Không có phiếu pending nào bị ảnh hưởng
        $this->assertSame(0, $affectedCount);

        // Sách ngừng cung cấp bị loại khỏi danh sách chọn
        $this->assertDatabaseMissing('book_selections', [
            'user_id' => $member->id,
            'book_id' => $retiredBook->id,
        ]);

        // Sách còn cung cấp vẫn được giữ lại
        $this->assertDatabaseHas('book_selections', [
            'user_id' => $member->id,
            'book_id' => $otherBook->id,
        ]);

        $this->assertSoftDeleted($retiredBook);
        $this->assertNotSoftDeleted($otherBook);
    }

    // 4. Một đầu sách ngừng cung cấp làm cả phiếu pending bị từ chối
    public function test_retiring_one_book_rejects_entire_pending_loan(): void
    {
        $member = $this->createUser();

        $book1 = $this->createBook();
        $book2 = $this->createBook();

        // Member yêu cầu mượn hai đầu sách cùng một phiếu
        $loan = app(LoanService::class)->requestLoan(
            $member->id,
            [$book1->id, $book2->id]
        );

        $this->assertSame(
            2,
            $loan->items()->count()
        );

        // Admin chỉ ngừng cung cấp book1
        $affectedCount = app(BookRetirementService::class)
            ->retire($book1);

        $this->assertSame(1, $affectedCount);

        // Toàn bộ phiếu bị từ chối
        $this->assertSame(
            'rejected',
            $loan->fresh()->status
        );

        // Hai LoanItem vẫn được giữ làm lịch sử
        $this->assertSame(
            2,
            $loan->items()->count()
        );

        // Book1 ngừng cung cấp, Book2 vẫn hoạt động
        $this->assertSoftDeleted($book1);
        $this->assertNotSoftDeleted($book2);
    }

    // 5. Chỉ từ chối những phiếu pending chứa sách ngừng cung cấp
    public function test_retiring_book_only_rejects_affected_pending_loans(): void
    {
        $member1 = $this->createUser();
        $member2 = $this->createUser();
        $member3 = $this->createUser();

        $retiredBook = $this->createBook();
        $otherBook = $this->createBook();

        $loanService = app(LoanService::class);

        // Hai Member yêu cầu cùng một đầu sách
        $loan1 = $loanService->requestLoan(
            $member1->id,
            [$retiredBook->id]
        );

        $loan2 = $loanService->requestLoan(
            $member2->id,
            [$retiredBook->id]
        );

        // Member thứ ba yêu cầu đầu sách khác
        $loan3 = $loanService->requestLoan(
            $member3->id,
            [$otherBook->id]
        );

        // Ngừng cung cấp retiredBook
        $affectedCount = app(BookRetirementService::class)
            ->retire($retiredBook);

        // Hai phiếu bị ảnh hưởng
        $this->assertSame(2, $affectedCount);

        $this->assertSame(
            'rejected',
            $loan1->fresh()->status
        );

        $this->assertSame(
            'rejected',
            $loan2->fresh()->status
        );

        // Phiếu không liên quan vẫn chờ duyệt
        $this->assertSame(
            'pending',
            $loan3->fresh()->status
        );

        $this->assertNotSoftDeleted($otherBook);
    }
}
