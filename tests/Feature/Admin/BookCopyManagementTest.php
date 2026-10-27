<?php

namespace Tests\Feature\Admin;

use App\Enums\BookCopyStatus;
use App\Enums\UserRole;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookCopyManagementTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Author $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Lập trình',
            'description' => 'Sách lập trình',
        ]);

        $this->author = Author::create([
            'name' => 'Tác giả Test',
            'bio' => 'Dữ liệu kiểm thử',
        ]);

        // Tạo Admin sử dụng Authentication thủ công.
        $admin = User::factory()->create();

        $admin->forceFill([
            'role' => UserRole::Admin,
            'is_active' => true,
        ])->save();

        $this->withSession([
            'manual_auth_user_id' => $admin->id,
        ]);
    }

    private function bookData(array $changes = []): array
    {
        return array_replace([
            'isbn' => '9780132350884',
            'title' => 'Lập trình PHP Test',
            'category_id' => $this->category->id,
            'author_ids' => [$this->author->id],
            'description' => 'Sách dùng để kiểm thử',
            'publisher' => 'NXB Test',
            'published_year' => 2024,
            'total_copies' => 5,
            'available_copies' => 3,
        ], $changes);
    }

    private function createBook(): Book
    {
        $this->post(
            route('admin.books.store'),
            $this->bookData()
        )
            ->assertRedirect(route('admin.books.index'))
            ->assertSessionHasNoErrors();

        return Book::where('isbn', '9780132350884')
            ->firstOrFail();
    }

    public function test_creating_book_creates_copies(): void
    {
        $book = $this->createBook();

        $this->assertSame(5, (int) $book->total_copies);
        $this->assertSame(3, (int) $book->available_copies);

        $this->assertSame(5, $book->copies()->count());

        $this->assertSame(
            3,
            $book->copies()
                ->where('status', BookCopyStatus::Available->value)
                ->count()
        );

        $this->assertSame(
            2,
            $book->copies()
                ->where('status', BookCopyStatus::Unverified->value)
                ->count()
        );
    }

    public function test_increasing_total_creates_more_copies(): void
    {
        $book = $this->createBook();

        $this->put(
            route('admin.books.update', $book),
            $this->bookData([
                'total_copies' => 7,
                'available_copies' => 0,
            ])
        )
            ->assertRedirect(route('admin.books.index'))
            ->assertSessionHasNoErrors();

        $book->refresh();

        $this->assertSame(7, (int) $book->total_copies);
        $this->assertSame(5, (int) $book->available_copies);
        $this->assertSame(7, $book->copies()->count());

        $this->assertSame(
            5,
            $book->copies()
                ->where('status', BookCopyStatus::Available->value)
                ->count()
        );
    }

    public function test_keeping_total_does_not_create_copies(): void
    {
        $book = $this->createBook();

        $this->put(
            route('admin.books.update', $book),
            $this->bookData()
        )
            ->assertRedirect(route('admin.books.index'))
            ->assertSessionHasNoErrors();

        $book->refresh();

        $this->assertSame(5, (int) $book->total_copies);
        $this->assertSame(3, (int) $book->available_copies);
        $this->assertSame(5, $book->copies()->count());
    }

    public function test_decreasing_total_is_rejected(): void
    {
        $book = $this->createBook();

        $this->put(
            route('admin.books.update', $book),
            $this->bookData(['total_copies' => 3])
        )->assertSessionHasErrors('total_copies');

        $book->refresh();

        $this->assertSame(5, (int) $book->total_copies);
        $this->assertSame(3, (int) $book->available_copies);
        $this->assertSame(5, $book->copies()->count());
    }

    public function test_out_of_sync_quantity_is_rejected(): void
    {
        $book = $this->createBook();

        // Cố tình tạo dữ liệu không đồng bộ.
        DB::table('books')
            ->where('id', $book->id)
            ->update(['available_copies' => 99]);

        $this->put(
            route('admin.books.update', $book),
            $this->bookData(['total_copies' => 7])
        )->assertSessionHasErrors('total_copies');

        $book->refresh();

        $this->assertSame(5, (int) $book->total_copies);
        $this->assertSame(5, $book->copies()->count());
    }
}
