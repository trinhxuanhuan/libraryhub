<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if ($request->hasFile('cover')) {
            $path = $request->file('cover')
                ->store('book-covers', 'public');

            $data['cover_path'] = $path;
        }

        $authorIds = $data['author_ids'];
        unset($data['author_ids']);

        DB::transaction(function () use ($data, $authorIds) {
            $book = Book::create($data);

            $book->authors()->sync($authorIds);
        });

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
        Book $book
    ): RedirectResponse {
        $data = $request->validated();

        $authorIds = $data['author_ids'];
        unset($data['author_ids']);

        DB::transaction(function () use ($book, $data, $authorIds) {
            $book->update($data);

            $book->authors()->sync($authorIds);
        });

        return redirect()
            ->route('admin.books.index')
            ->with('success', __('message.books.updated'));
    }

    public function destroy(Book $book): RedirectResponse
    {
        $book->delete();

        return redirect()
            ->route('admin.books.index')
            ->with('success', __('message.books.deleted'));
    }
}
