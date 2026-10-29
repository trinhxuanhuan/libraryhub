<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin\AuthorController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookCatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Management\DashboardController;
use App\Http\Controllers\Management\LoanController as ManagementLoanController;
use App\Http\Controllers\Member\BookSelectionController;
use App\Http\Controllers\Member\LoanController as MemberLoanController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\CheckManualAuth;
use App\Models\Book;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::get('/about', [AboutController::class, 'index'])
    ->name('about');

Route::get('/books', [BookCatalogController::class, 'index'])
    ->name('books.index');

Route::get('/books/{book}', [BookCatalogController::class, 'show'])
    ->name('books.show');
Route::get('/books/{book}/borrow', function (Book $book) {
    return redirect()->route('books.show', $book);
})
    ->middleware('manual.auth')
    ->name('books.borrow');

// Book, Author, Category Management - Admin & Librarian
Route::middleware(['manual.auth', 'role:admin,librarian'])
    ->group(function () {
        Route::resource('admin/categories', CategoryController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->names('admin.categories');

        Route::resource('admin/authors', AuthorController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->names('admin.authors');

        Route::get('admin/books/{book}/retire', [BookController::class, 'retire'])
            ->name('admin.books.retire');

        Route::resource('admin/books', BookController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->names('admin.books');
    });

// User Management - Admin Only
Route::middleware(['manual.auth', 'role:admin'])
    ->group(function () {
        Route::get('admin/users', [UserController::class, 'index'])
            ->name('admin.users.index');

        Route::get('admin/users/{user}/edit', [UserController::class, 'edit'])
            ->name('admin.users.edit');

        Route::put('admin/users/{user}', [UserController::class, 'update'])
            ->name('admin.users.update');
    });

// Authentication Routes
Route::middleware('manual.guest')
    ->group(function () {
        // Đăng ký
        Route::get('/register', [RegisterController::class, 'create'])
            ->name('register');

        Route::post('/register', [RegisterController::class, 'store'])
            ->name('register.store');

        // Đăng nhập
        Route::get('/login', [AuthController::class, 'create'])
            ->name('login');

        Route::post('/login', [AuthController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('login.store');
    });

// Hồ sơ cá nhân - mọi tài khoản đã đăng nhập
Route::middleware('manual.auth')
    ->prefix('profile')
    ->name('profile.')
    ->group(function () {
        Route::get('/', [ProfileController::class, 'show'])
            ->name('show');

        Route::patch('/', [ProfileController::class, 'update'])
            ->name('update');

        Route::patch('/password', [ProfileController::class, 'requestPasswordChange'])
            ->middleware('throttle:5,1')
            ->name('password.request');

        Route::get('/password/verify', [ProfileController::class, 'verifyPasswordChange'])
            ->name('password.verify');

        Route::post('/password/verify', [ProfileController::class, 'confirmPasswordChange'])
            ->middleware('throttle:10,1')
            ->name('password.confirm');

        Route::post('/password/resend', [ProfileController::class, 'resendPasswordChange'])
            ->middleware('throttle:5,1')
            ->name('password.resend');
    });

// Đăng xuất
Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('manual.auth')
    ->name('logout');

// Member Routes
Route::middleware([CheckManualAuth::class, 'role:member'])
    ->prefix('member')
    ->name('member.')
    ->group(function () {
        // Gửi yêu cầu mượn sách
        Route::post('/loans', [MemberLoanController::class, 'store'])
            ->name('loans.store');

        // Phiếu mượn của tôi
        Route::get('/loans', [MemberLoanController::class, 'myLoans'])
            ->name('loans.index');

        // Gia hạn sách
        Route::post('/loans/{loan}/renew', [MemberLoanController::class, 'renew'])
            ->name('loans.renew');

        // Danh sách sách đã chọn
        Route::get('/selection', [BookSelectionController::class, 'index'])
            ->name('selection.index');

        // Thêm sách vào danh sách chọn
        Route::post('/selection/books/{book}', [BookSelectionController::class, 'store'])
            ->name('selection.add');

        // Xóa sách khỏi danh sách chọn
        Route::delete('/selection/books/{bookId}', [BookSelectionController::class, 'destroy'])
            ->name('selection.remove');

        // Gửi toàn bộ danh sách yêu cầu mượn
        Route::post('/selection/submit', [BookSelectionController::class, 'submit'])
            ->name('selection.submit');
    });

// Management Routes - Admin & Librarian
Route::middleware(['manual.auth', 'role:admin,librarian'])
    ->prefix('management')
    ->name('management.')
    ->group(function () {
        // Dashboard và thống kê
        Route::get('/dashboard', [
            DashboardController::class,
            'index',
        ])->name('dashboard');

        // Danh sách phiếu mượn
        Route::get('/loans', [
            ManagementLoanController::class,
            'index',
        ])->name('loans.index');

        // Tạo phiếu mượn hộ thành viên
        Route::get('/loans/create', [
            ManagementLoanController::class,
            'create',
        ])->name('loans.create');

        Route::post('/loans', [
            ManagementLoanController::class,
            'store',
        ])->name('loans.store');

        // Chi tiết phiếu mượn
        Route::get('/loans/{loan}', [
            ManagementLoanController::class,
            'show',
        ])->name('loans.show');

        // Duyệt và giao sách
        Route::post('/loans/{loan}/issue', [
            ManagementLoanController::class,
            'issue',
        ])->name('loans.issue');

        // Từ chối phiếu mượn
        Route::post('/loans/{loan}/reject', [
            ManagementLoanController::class,
            'reject',
        ])->name('loans.reject');

        // Ghi nhận trả sách, sách hỏng hoặc mất
        Route::post('/loans/{loan}/items/{item}/return', [
            ManagementLoanController::class,
            'returnItem',
        ])->name('loans.items.return');

        // Xác nhận đã thu tiền phạt
        Route::post('/loans/{loan}/items/{item}/confirm-payment', [
            ManagementLoanController::class,
            'confirmFinePayment',
        ])->name('loans.items.confirm-payment');
    });
