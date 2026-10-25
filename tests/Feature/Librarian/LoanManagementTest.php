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

    public function test_issue_loan_rolls_back_when_one_copy_is_unavailable(): void
    {
        // Chuẩn bị hai đầu sách, mỗi đầu sách có một bản vật lý
        [$book1, $copy1] = $this->createBookWithCopy();
        [$book2, $copy2] = $this->createBookWithCopy();

        // Tạo phiếu yêu cầu mượn cả hai sách
        $loan = $this->createPendingLoan($book1, $book2);

        $items = $loan->items()
            ->orderBy('book_id')
            ->get();

        // Giả lập bản sách thứ hai không còn khả dụng
        $copy2->update([
            'status' => BookCopyStatus::Unverified->value,
        ]);

        $book2->update([
            'available_copies' => 0,
        ]);

        // Librarian cố giao cả hai bản sách
        $this->post(
            route('librarian.loans.issue', $loan),
            [
                'copies' => [
                    $items[0]->id => $copy1->id,
                    $items[1]->id => $copy2->id,
                ],
            ]
        )->assertSessionHasErrors('copies');

        // Phiếu vẫn đang chờ xử lý
        $this->assertSame('pending', $loan->fresh()->status);

        // BookCopy thứ nhất phải được rollback về Available
        $this->assertSame(
            BookCopyStatus::Available->value,
            $copy1->fresh()->getRawOriginal('status')
        );

        // Số lượng sách thứ nhất không được giảm
        $this->assertSame(
            1,
            (int) $book1->fresh()->available_copies
        );

        // Số lượng sách thứ hai vẫn bằng 0
        $this->assertSame(
            0,
            (int) $book2->fresh()->available_copies
        );

        // Không LoanItem nào được ghi nhận là đã giao
        foreach ($items as $item) {
            $this->assertNull($item->fresh()->book_copy_id);
        }

        // Không ghi nhận ngày giao hoặc người giao
        $this->assertNull($loan->fresh()->borrowed_at);
        $this->assertNull($loan->fresh()->issued_by);
    }

    public function test_librarian_can_search_loans_by_member_email(): void
    {
        [$book1] = $this->createBookWithCopy();
        [$book2] = $this->createBookWithCopy();

        $loan1 = $this->createPendingLoan($book1);

        $otherMember = User::factory()->create([
            'name' => 'Other Test Member',
            'email' => 'other.member@example.test',
        ]);

        $loan2 = Loan::create([
            'user_id' => $otherMember->id,
            'status' => 'pending',
        ]);

        $loan2->items()->create([
            'book_id' => $book2->id,
            'book_copy_id' => null,
        ]);

        $this->get(route('librarian.loans.index', [
            'search' => $this->member->email,
        ]))
            ->assertOk()
            ->assertSeeText('#'.$loan1->id)
            ->assertDontSeeText('#'.$loan2->id);
    }

    public function test_librarian_can_filter_overdue_loans(): void
    {
        [$book1] = $this->createBookWithCopy();
        [$book2] = $this->createBookWithCopy();

        $overdueLoan = $this->createPendingLoan($book1);
        $normalLoan = $this->createPendingLoan($book2);

        $overdueLoan->update([
            'status' => 'borrowed',
            'due_at' => now()->subDay(),
        ]);

        $normalLoan->update([
            'status' => 'borrowed',
            'due_at' => now()->addDays(7),
        ]);

        $this->get(route('librarian.loans.index', [
            'status' => 'overdue',
        ]))
            ->assertOk()
            ->assertSeeText('#'.$overdueLoan->id)
            ->assertDontSeeText('#'.$normalLoan->id);
    }

    public function test_librarian_can_filter_loans_by_request_date(): void
    {
        [$book1] = $this->createBookWithCopy();
        [$book2] = $this->createBookWithCopy();

        $oldLoan = $this->createPendingLoan($book1);
        $newLoan = $this->createPendingLoan($book2);

        $oldLoan->forceFill([
            'created_at' => now()->subDays(10),
        ])->saveQuietly();

        $this->get(route('librarian.loans.index', [
            'from_date' => now()->subDays(2)->toDateString(),
            'to_date' => now()->toDateString(),
        ]))
            ->assertOk()
            ->assertSeeText('#'.$newLoan->id)
            ->assertDontSeeText('#'.$oldLoan->id);
    }

    public function test_librarian_cannot_filter_with_invalid_date_range(): void
    {
        $this->get(route('librarian.loans.index', [
            'from_date' => '2026-10-25',
            'to_date' => '2026-10-20',
        ]))
            ->assertRedirect()
            ->assertSessionHasErrors('to_date');
    }

    public function test_admin_can_view_and_issue_loan(): void
    {
        $admin = User::factory()->create();

        $admin->forceFill([
            'role' => UserRole::Admin,
            'is_active' => true,
        ])->save();

        // Giả lập Admin đã đăng nhập
        $this->withSession([
            'manual_auth_user_id' => $admin->id,
        ]);

        [$book, $copy] = $this->createBookWithCopy();

        $loan = $this->createPendingLoan($book);

        $item = $loan->items()->firstOrFail();

        // Admin được truy cập danh sách
        $this->get(route('librarian.loans.index'))
            ->assertOk();

        // Admin được xem chi tiết
        $this->get(route('librarian.loans.show', $loan))
            ->assertOk();

        // Admin được duyệt và giao sách
        $this->post(
            route('librarian.loans.issue', $loan),
            [
                'copies' => [
                    $item->id => $copy->id,
                ],
            ]
        )
            ->assertRedirect(route('librarian.loans.show', $loan))
            ->assertSessionHasNoErrors();

        $this->assertSame('borrowed', $loan->fresh()->status);

        $this->assertSame(
            $admin->id,
            $loan->fresh()->issued_by
        );
    }

    public function test_member_cannot_issue_loan(): void
    {
        [$book, $copy] = $this->createBookWithCopy();

        $loan = $this->createPendingLoan($book);

        $item = $loan->items()->firstOrFail();

        // Đăng nhập bằng Member
        $this->withSession([
            'manual_auth_user_id' => $this->member->id,
        ]);

        $this->post(
            route('librarian.loans.issue', $loan),
            [
                'copies' => [
                    $item->id => $copy->id,
                ],
            ]
        )->assertForbidden();

        // Phiếu không được thay đổi
        $this->assertSame('pending', $loan->fresh()->status);

        // Tồn kho không được giảm
        $this->assertSame(
            1,
            (int) $book->fresh()->available_copies
        );
    }
}
