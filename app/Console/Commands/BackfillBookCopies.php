<?php

namespace App\Console\Commands;

use App\Enums\BookCopyStatus;
use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillBookCopies extends Command
{
    protected $signature = 'books:backfill-copies';

    protected $description = 'Create book copies from existing book quantities';

    public function handle(): int
    {
        if (BookCopy::query()->exists()) {
            $this->error('Book copies already exist.');

            return self::FAILURE;
        }

        $books = Book::withTrashed()->get();

        foreach ($books as $book) {
            if ($book->available_copies > $book->total_copies) {
                $this->error("Invalid quantities for book ID {$book->id}");

                return self::FAILURE;
            }
        }

        DB::transaction(function () use ($books) {
            foreach ($books as $book) {
                for ($i = 1; $i <= $book->total_copies; $i++) {
                    BookCopy::create([
                        'book_id' => $book->id,

                        'barcode' => sprintf(
                            'COPY-%06d-%04d',
                            $book->id,
                            $i
                        ),

                        'status' => $i <= $book->available_copies
                            ? BookCopyStatus::Available
                            : BookCopyStatus::Unverified,
                    ]);
                }
            }
        });

        $this->info('Book copies created successfully.');

        return self::SUCCESS;
    }
}
