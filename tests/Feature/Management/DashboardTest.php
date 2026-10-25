<?php

namespace Tests\Feature\Management;

use App\Enums\BookCopyStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $librarian;

    private User $member;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser(UserRole::Admin);
        $this->librarian = $this->createUser(UserRole::Librarian);
        $this->member = $this->createUser(UserRole::Member);

        $this->category = Category::create([
            'name' => 'Dashboard Test',
            'description' => 'Dữ liệu thống kê',
        ]);
    }

    private function createUser(UserRole $role): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'is_active' => true,
        ])->save();

        return $user;
    }

    private function loginAs(User $user): void
    {
        $this->withSession([
            'manual_auth_user_id' => $user->id,
        ]);
    }

    private function createBook(): Book
    {
        return Book::factory()->create([
            'category_id' => $this->category->id,
            'cover_path' => null,
            'total_copies' => 1,
            'available_copies' => 1,
        ]);
    }

    // Tạo dữ liệu mô phỏng một lần giao sách thực tế
    private function createIssuedLoan(
        Book $book,
        Carbon $borrowedAt,
        string $status = 'borrowed'
    ): Loan {
        $isReturned = $status === 'returned';

        $loan = Loan::create([
            'user_id' => $this->member->id,
            'status' => $status,
            'borrowed_at' => $borrowedAt,
            'due_at' => $borrowedAt->copy()->addDays(14),
            'returned_at' => $isReturned
                ? $borrowedAt->copy()->addDay()
                : null,
        ]);

        $copy = $book->copies()->create([
            'barcode' => "DASH-{$book->id}-{$loan->id}",
            'status' => $isReturned
                ? BookCopyStatus::Available->value
                : BookCopyStatus::Borrowed->value,
        ]);

        $loan->items()->create([
            'book_id' => $book->id,
            'book_copy_id' => $copy->id,
            'returned_at' => $isReturned
                ? $borrowedAt->copy()->addDay()
                : null,
            'resolved_at' => $isReturned
                ? $borrowedAt->copy()->addDay()
                : null,
        ]);

        return $loan;
    }

    public function test_only_admin_and_librarian_can_access_dashboard(): void
    {
        $this->loginAs($this->admin);

        $this->get(route('management.dashboard'))
            ->assertOk();

        $this->loginAs($this->librarian);

        $this->get(route('management.dashboard'))
            ->assertOk();

        $this->loginAs($this->member);

        $this->get(route('management.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_statistics_are_correct(): void
    {
        $this->createBook();
        $this->createBook();

        // Một phiếu chờ xử lý
        Loan::create([
            'user_id' => $this->member->id,
            'status' => 'pending',
        ]);

        // Một phiếu đang mượn, đã quá hạn
        Loan::create([
            'user_id' => $this->member->id,
            'status' => 'borrowed',
            'borrowed_at' => now()->subDays(20),
            'due_at' => now()->subDay(),
        ]);

        // Một phiếu đang mượn, chưa quá hạn
        Loan::create([
            'user_id' => $this->member->id,
            'status' => 'borrowed',
            'borrowed_at' => now()->subDay(),
            'due_at' => now()->addDays(7),
        ]);

        $this->loginAs($this->admin);

        $this->get(route('management.dashboard'))
            ->assertOk()
            ->assertViewHas('statistics', function ($stats) {
                return $stats['books'] === 2
                    && $stats['members'] === 1
                    && $stats['borrowed'] === 2
                    && $stats['overdue'] === 1;
            });
    }

    public function test_monthly_chart_counts_issued_loans(): void
    {
        $this->travelTo(
            Carbon::parse('2026-10-25 12:00:00')
        );

        $book = $this->createBook();

        // Giao một phiếu trong tháng 6
        $this->createIssuedLoan(
            $book,
            Carbon::parse('2026-06-10 10:00:00'),
            'returned'
        );

        // Giao một phiếu trong tháng 10
        $this->createIssuedLoan(
            $book,
            Carbon::parse('2026-10-12 10:00:00')
        );

        // Phiếu pending không được tính
        Loan::create([
            'user_id' => $this->member->id,
            'status' => 'pending',
        ]);

        $this->loginAs($this->librarian);

        $this->get(route('management.dashboard'))
            ->assertOk()
            ->assertViewHas(
                'monthlyBorrowings',
                function ($months) {
                    // Tháng 5 đến tháng 10
                    return $months->pluck('count')->all()
                        === [0, 1, 0, 0, 0, 1];
                }
            );
    }

    public function test_top_five_books_are_ranked_by_actual_borrowings(): void
    {
        $this->travelTo(
            Carbon::parse('2026-10-25 12:00:00')
        );

        $books = [];

        // Tạo 6 đầu sách có lượt mượn từ 1 đến 6
        for ($i = 1; $i <= 6; $i++) {
            $book = $this->createBook();
            $books[] = $book;

            for ($j = 0; $j < $i; $j++) {
                $this->createIssuedLoan(
                    $book,
                    now()->subDays(10),
                    'returned'
                );
            }
        }

        // Sách thứ 7 chỉ được yêu cầu, chưa giao
        $pendingBook = $this->createBook();

        $pendingLoan = Loan::create([
            'user_id' => $this->member->id,
            'status' => 'pending',
        ]);

        $pendingLoan->items()->create([
            'book_id' => $pendingBook->id,
            'book_copy_id' => null,
        ]);

        $this->loginAs($this->admin);

        $this->get(route('management.dashboard'))
            ->assertOk()
            ->assertViewHas('topBooks', function ($topBooks) use ($books) {
                if ($topBooks->count() !== 5) {
                    return false;
                }

                // Sắp xếp từ sách có 6 lượt xuống 2 lượt
                $expectedIds = collect(array_reverse($books))
                    ->take(5)
                    ->pluck('id')
                    ->all();

                $actualIds = $topBooks
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                return $actualIds === $expectedIds
                    && (int) $topBooks->first()->borrow_count === 6;
            });
    }
}
