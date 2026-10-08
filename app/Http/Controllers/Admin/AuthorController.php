<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthorRequest;
use App\Models\Author;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(): View
    {
        $authors = Author::withCount('books')
            ->orderBy('id')
            ->get();

        return view('admin.authors.index', [
            'authors' => $authors,
        ]);
    }

    public function create(): View
    {
        return view('admin.authors.create');
    }

    public function store(AuthorRequest $request): RedirectResponse
    {
        Author::create($request->validated());

        return redirect()
            ->route('admin.authors.index')
            ->with('success', 'Đã thêm tác giả thành công.');
    }
    public function edit(Author $author): View
    {
        return view('admin.authors.edit', [
            'author' => $author,
        ]);
    }

    public function update(AuthorRequest $request, Author $author): RedirectResponse
    {
        $author->update($request->validated());

    return redirect()
        ->route('admin.authors.index')
        ->with('success', 'Đã cập nhật tác giả thành công.');
    }
}


