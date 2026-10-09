<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::orderBy('id')->get();

        return view('admin.categories.index', [
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('message.categories.created'));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(
        CategoryRequest $request,
        Category $category
    ): RedirectResponse {
        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('message.categories.updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $hasBooks = $category->books()
            ->withTrashed()
            ->exists();

        if ($hasBooks) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', __('message.categories.delete_blocked'));
        }

        try {
            $category->delete();
        } catch (QueryException $error) {
            // MySQL chặn xóa khi dữ liệu vẫn đang được tham chiếu.
            if ((int) ($error->errorInfo[1] ?? 0) !== 1451) {
                throw $error;
            }

            return redirect()
                ->route('admin.categories.index')
                ->with('error', __('message.categories.deleted_blocked'));
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('message.categories.deleted'));
    }
}
