<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BookCopyStatus;
use App\Models\Book;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BookCopySeeder extends Seeder
{
    public function run(): void
    {
        Book::query()->chunkById(100, function ($books) {
            foreach ($books as $book) {
                DB::transaction(function () use ($book) {
                    $book = Book::query()
                        ->lockForUpdate()
                        ->findOrFail($book->id);

                    $total = (int) $book->total_copies;
                    $available = (int) $book->available_copies;

                    // Kiểm tra số lượng hợp lệ
                    if ($total < 0 || $available < 0 || $available > $total) {
                        throw new RuntimeException(
                            "Số lượng không hợp lệ ở Book ID {$book->id}"
                        );
                    }

                    $existingCopies = $book->copies()->count();

                    // Tránh tạo trùng khi chạy Seeder nhiều lần
                    if ($existingCopies > 0) {
                        if ($existingCopies !== $total) {
                            throw new RuntimeException(
                                "Book ID {$book->id} có số bản không đồng bộ."
                            );
                        }

                        return;
                    }

                    // Tạo bản sách vật lý theo số lượng trong books
                    for ($i = 1; $i <= $total; $i++) {
                        $book->copies()->create([
                            'barcode' => sprintf(
                                'COPY-%06d-%04d',
                                $book->id,
                                $i
                            ),
                            'status' => $i <= $available
                                ? BookCopyStatus::Available->value
                                : BookCopyStatus::Unverified->value,
                        ]);
                    }
                });
            }
        });
    }
}
