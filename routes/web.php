<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\AuthorController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Librarian\LoanController as LibrarianLoanController;
use App\Http\Controllers\Member\BookSelectionController;
use App\Http\Controllers\Member\LoanController as MemberLoanController;
use App\Http\Middleware\CheckManualAuth;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::get('/about', [AboutController::class, 'index'])
    ->name('about');

Route::middleware(['manual.auth', 'role:admin'])->group(function () {
    Route::resource('admin/categories', CategoryController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->names('admin.categories');

    Route::resource('admin/authors', AuthorController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->names('admin.authors');

    Route::resource('admin/books', BookController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
        ->names('admin.books');

    Route::get('admin/users', [UserController::class, 'index'])
        ->name('admin.users.index');

    Route::get('admin/users/{user}/edit', [UserController::class, 'edit'])
        ->name('admin.users.edit');

    Route::put('admin/users/{user}', [UserController::class, 'update'])
        ->name('admin.users.update');
});

Route::middleware('manual.guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])
        ->name('register');

    Route::post('/register', [RegisterController::class, 'store'])
        ->name('register.store');

    Route::get('/login', [AuthController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('manual.auth')
    ->name('logout');

Route::middleware([CheckManualAuth::class, 'role:member'])
    ->prefix('member')
    ->name('member.')
    ->group(function () {
        Route::get('/books', [MemberLoanController::class, 'index'])
            ->name('books.index');

        Route::post('/loans', [MemberLoanController::class, 'store'])
            ->name('loans.store');

        Route::get('/loans', [MemberLoanController::class, 'myLoans'])
            ->name('loans.index');

        Route::get('/books/{book}', [MemberLoanController::class, 'show'])
            ->name('books.show');
        // Danh sách sách muốn mượn
        Route::get('/selection', [BookSelectionController::class, 'index'])
            ->name('selection.index');

        // Thêm sách từ trang chi tiết
        Route::post('/selection/books/{book}', [BookSelectionController::class, 'store'])
            ->name('selection.add');

        // Bỏ một sách khỏi danh sách
        Route::delete('/selection/books/{bookId}', [BookSelectionController::class, 'destroy'])
            ->name('selection.remove');

        // Xác nhận gửi toàn bộ yêu cầu
        Route::post('/selection/submit', [BookSelectionController::class, 'submit'])
            ->name('selection.submit');
    });

Route::middleware(['manual.auth', 'role:librarian'])
    ->prefix('librarian')
    ->name('librarian.')
    ->group(function () {
        Route::get('/loans', [LibrarianLoanController::class, 'index'])
            ->name('loans.index');

        Route::get('/loans/{loan}', [LibrarianLoanController::class, 'show'])
            ->name('loans.show');

        Route::post('/loans/{loan}/issue', [LibrarianLoanController::class, 'issue'])
            ->name('loans.issue');

        Route::post('/loans/{loan}/reject', [LibrarianLoanController::class, 'reject'])
            ->name('loans.reject');

        Route::post('/loans/{loan}/items/{item}/return', [
            LibrarianLoanController::class,
            'returnItem',
        ])->name('loans.items.return');
    });
