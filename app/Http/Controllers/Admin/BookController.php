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
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(): View
    {
        $books = Book::with(['category', 'authors'])
            ->orderBy('id')
            ->get();

        return view('admin.books.index', [
            'books' => $books,
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
}
