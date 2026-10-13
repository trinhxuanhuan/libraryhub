<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;
use RuntimeException;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::pluck('id');
        $authorIds = Author::pluck('id');

        if ($categoryIds->isEmpty() || $authorIds->isEmpty()) {
            throw new RuntimeException(
                'Cần có thể loại và tác giả trước khi tạo sách.'
            );
        }

        $remaining = max(0, 100 - Book::count());

        for ($i = 0; $i < $remaining; $i++) {
            // Tạo một cuốn sách
            $book = Book::factory()->create([
                'category_id' => $categoryIds->random(),
            ]);

            $authorCount = random_int(
                1,
                min(3, $authorIds->count())
            );

            $selectedAuthors = $authorIds
                ->random($authorCount)
                ->all();

            $book->authors()->attach($selectedAuthors);
        }
    }
}
