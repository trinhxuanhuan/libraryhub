<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookCopyStatus;
use App\Models\Book;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class BookService
{
    public function create(array $data, ?UploadedFile $cover): Book
    {
        $authorIds = $data['author_ids'];

        unset($data['author_ids'], $data['cover']);

        $newCoverPath = null;

        try {
            if ($cover !== null) {
                $newCoverPath = $this->storeCover($cover);
                $data['cover_path'] = $newCoverPath;
            }

            return DB::transaction(function () use ($data, $authorIds): Book {
                $book = Book::create($data);

                $book->authors()->sync($authorIds);

                for ($i = 1; $i <= $book->total_copies; $i++) {
                    $book->copies()->create([
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

                return $book;
            });
        } catch (Throwable $e) {
            if ($newCoverPath !== null) {
                Storage::disk('public')->delete($newCoverPath);
            }

            throw $e;
        }
    }

    public function update(
        Book $book,
        array $data,
        ?UploadedFile $cover
    ): void {
        $authorIds = $data['author_ids'];

        unset($data['author_ids'], $data['cover']);

        $newCoverPath = null;
        $oldCoverPath = null;

        try {
            if ($cover !== null) {
                $newCoverPath = $this->storeCover($cover);
                $data['cover_path'] = $newCoverPath;
            }

            DB::transaction(function () use (
                $book,
                $data,
                $authorIds,
                &$oldCoverPath
            ): void {
                $book = Book::query()
                    ->lockForUpdate()
                    ->findOrFail($book->id);

                $oldCoverPath = $book->cover_path;

                $currentTotal = $book->copies()->count();

                $currentAvailable = $book->copies()
                    ->where('status', BookCopyStatus::Available->value)
                    ->count();

                if (
                    $currentTotal !== (int) $book->total_copies ||
                    $currentAvailable !== (int) $book->available_copies
                ) {
                    throw ValidationException::withMessages([
                        'total_copies' => __('message.books.quantity_out_of_sync'),
                    ]);
                }

                $newTotal = (int) $data['total_copies'];

                if ($newTotal < $currentTotal) {
                    throw ValidationException::withMessages([
                        'total_copies' => __('message.books.decrease_copies_not_supported'),
                    ]);
                }

                $addedCopies = $newTotal - $currentTotal;

                $book->update([
                    ...$data,
                    'available_copies' => $currentAvailable + $addedCopies,
                ]);

                $book->authors()->sync($authorIds);

                for ($i = $currentTotal + 1; $i <= $newTotal; $i++) {
                    $book->copies()->create([
                        'barcode' => sprintf(
                            'COPY-%06d-%04d',
                            $book->id,
                            $i
                        ),
                        'status' => BookCopyStatus::Available,
                    ]);
                }
            });
        } catch (Throwable $e) {
            if ($newCoverPath !== null) {
                Storage::disk('public')->delete($newCoverPath);
            }

            throw $e;
        }

        if ($newCoverPath !== null && $oldCoverPath) {
            Storage::disk('public')->delete($oldCoverPath);
        }
    }

    private function storeCover(UploadedFile $cover): string
    {
        $path = $cover->store('book-covers', 'public');

        if ($path === false) {
            throw new RuntimeException('Unable to store book cover.');
        }

        return $path;
    }
}
