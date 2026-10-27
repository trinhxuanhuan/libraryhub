<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Services\BookRetirementService;
use App\Services\BookService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $search = trim($filters['search'] ?? '');
        $categoryId = $filters['category_id'] ?? null;

        $query = Book::with(['category', 'authors'])
            ->orderBy('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $books = $query->paginate(10)->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('admin.books.index', [
            'books' => $books,
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('admin.books.create', [
            'book' => new Book,
            'categories' => Category::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
        ]);
    }

    public function store(
        StoreBookRequest $request,
        BookService $bookService
    ): RedirectResponse {
        $bookService->create(
            $request->validated(),
            $request->file('cover')
        );

        return redirect()
            ->route('admin.books.index')
            ->with('success', __('message.books.created'));
    }

    public function edit(Book $book): View
    {
        $book->load('authors');

        return view('admin.books.edit', [
            'book' => $book,
            'categories' => Category::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
        ]);
    }

    public function update(
        UpdateBookRequest $request,
        Book $book,
        BookService $bookService
    ): RedirectResponse {
        $bookService->update(
            $book,
            $request->validated(),
            $request->file('cover')
        );

        return redirect()
            ->route('admin.books.index')
            ->with('success', __('message.books.updated'));
    }

    public function destroy(
        Book $book,
        BookRetirementService $retirementService
    ): RedirectResponse {
        $rejectedCount = $retirementService->retire($book);

        return redirect()
            ->route('admin.books.index')
            ->with('success', __('message.books.retired', [
                'count' => $rejectedCount,
            ]));
    }

    public function retire(Book $book): View
    {
        $pendingLoansCount = Loan::query()
            ->where('status', 'pending')
            ->whereHas('items', function (Builder $query) use ($book) {
                $query->where('book_id', $book->id);
            })
            ->count();

        return view('admin.books.retire', compact(
            'book',
            'pendingLoansCount'
        ));
    }
}
