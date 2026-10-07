<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Đã thêm thể loại thành công.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category
    ): RedirectResponse {
        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Đã cập nhật thể loại thành công.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $hasBooks = DB::table('books')
            ->where('category_id', $category->id)
            ->exists();

        if ($hasBooks) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Không thể xóa thể loại đang có sách.');
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
                ->with('error', 'Không thể xóa thể loại đang có sách.');
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Đã xóa thể loại thành công.');
    }
}
