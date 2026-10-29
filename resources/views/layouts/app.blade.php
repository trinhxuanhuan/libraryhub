<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LibraryHub')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex flex-column min-vh-100">
    @php
        $currentUser = app(\App\Services\ManualAuthenticator::class)->user(request());

        $isAdmin = $currentUser?->role === \App\Enums\UserRole::Admin;
        $isLibrarian = $currentUser?->role === \App\Enums\UserRole::Librarian;
        $isMember = $currentUser?->role === \App\Enums\UserRole::Member;

        $canManageLibrary = $isAdmin || $isLibrarian;
        $showManagementLayout = $canManageLibrary
            && request()->routeIs('management.*', 'admin.*');
    @endphp

    {{-- Navbar dùng chung cho Guest, Member, Librarian và Admin. --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary libraryhub-topbar border-bottom shadow-sm sticky-top">
        <div class="container-xl">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('home') }}">
                <span class="libraryhub-brand-mark">LH</span>
                <span>LibraryHub</span>
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainMenu"
                aria-controls="mainMenu"
                aria-expanded="false"
                aria-label="Mở menu"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-1">
                    <li class="nav-item">
                        <a
                            class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                            href="{{ route('home') }}"
                        >
                            Trang chủ
                        </a>
                    </li>

                    {{-- Danh mục sách luôn công khai, kể cả khi chưa đăng nhập. --}}
                    <li class="nav-item">
                        <a
                            class="nav-link {{ request()->routeIs('books.*') ? 'active' : '' }}"
                            href="{{ route('books.index') }}"
                        >
                            Danh mục sách
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}"
                            href="{{ route('about') }}"
                        >
                            Giới thiệu
                        </a>
                    </li>

                    @if ($isMember)
                        <li class="nav-item">
                            <a
                                class="nav-link {{ request()->routeIs('member.selection.*') ? 'active' : '' }}"
                                href="{{ route('member.selection.index') }}"
                            >
                                Sách đã chọn
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                class="nav-link {{ request()->routeIs('member.loans.*') ? 'active' : '' }}"
                                href="{{ route('member.loans.index') }}"
                            >
                                Phiếu mượn của tôi
                            </a>
                        </li>
                    @endif

                    @if ($canManageLibrary)
                        <li class="nav-item">
                            <a
                                class="nav-link {{ $showManagementLayout ? 'active' : '' }}"
                                href="{{ route('management.dashboard') }}"
                            >
                                Khu vực quản lý
                            </a>
                        </li>
                    @endif
                </ul>

                <ul class="navbar-nav ms-lg-auto align-items-lg-center gap-lg-2">
                    @if ($currentUser === null)
                        <li class="nav-item">
                            <a
                                class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}"
                                href="{{ route('login') }}"
                            >
                                Đăng nhập
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="btn btn-light text-primary" href="{{ route('register') }}">
                                Đăng ký
                            </a>
                        </li>
                    @else
                        <li class="nav-item dropdown">
                            <button
                                class="btn btn-outline-light dropdown-toggle"
                                type="button"
                                id="accountMenu"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                {{ $currentUser->name }}
                            </button>

                            <ul class="dropdown-menu dropdown-menu-lg-end" aria-labelledby="accountMenu">
                                <li>
                                    <span class="dropdown-item-text text-muted small">
                                        @if ($isAdmin)
                                            Quản trị viên
                                        @elseif ($isLibrarian)
                                            Thủ thư
                                        @else
                                            Thành viên
                                        @endif
                                    </span>
                                </li>

                                <li>
                                    <a
                                        class="dropdown-item {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                                        href="{{ route('profile.show') }}"
                                    >
                                        Hồ sơ cá nhân
                                    </a>
                                </li>

                                <li><hr class="dropdown-divider"></li>

                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            Đăng xuất
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    @if ($showManagementLayout)
        {{-- Bố cục quản lý dùng chung: sidebar chỉ hiện trên route quản lý. --}}
        <div class="container-fluid px-3 px-xl-4 py-4 flex-grow-1">
            <div class="row g-4">
                <div class="col-lg-3 col-xl-2">
                    @include('partials.management-sidebar')
                </div>

                <main id="main-content" class="col-lg-9 col-xl-10 management-content">
                    @yield('content')
                </main>
            </div>
        </div>
    @else
        <main id="main-content" class="container-xl py-4 flex-grow-1">
            @yield('content')
        </main>
    @endif

    {{-- Flash messages: Bootstrap Toast được khởi tạo bởi resources/js/app.js. --}}
    @if (session('success') || session('error') || $errors->any())
        <div
            class="toast-container position-fixed bottom-0 end-0 p-3 flash-toast-container"
            aria-label="Thông báo hệ thống"
        >
            @if (session('success'))
                <div
                    class="toast align-items-center text-bg-success border-0"
                    role="status"
                    aria-live="polite"
                    aria-atomic="true"
                    data-flash-toast
                    data-bs-autohide="true"
                    data-bs-delay="4000"
                >
                    <div class="d-flex">
                        <div class="toast-body">{{ session('success') }}</div>
                        <button
                            type="button"
                            class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast"
                            aria-label="Đóng thông báo"
                        ></button>
                    </div>
                </div>
            @endif

            {{-- Validation errors --}}
            @if ($errors->any())
                <div
                    class="toast align-items-center text-bg-danger border-0"
                    role="alert"
                    aria-live="assertive"
                    aria-atomic="true"
                    data-flash-toast
                    data-bs-autohide="true"
                    data-bs-delay="8000"
                >
                    <div class="d-flex">
                        <div class="toast-body">
                            <div class="fw-semibold mb-1">
                                Không thể thực hiện thao tác
                            </div>

                            @foreach (collect($errors->all())->unique() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>

                        <button
                            type="button"
                            class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast"
                            aria-label="Đóng thông báo"
                        ></button>
                    </div>
                </div>
            @endif

            {{-- Thông báo lỗi nghiệp vụ --}}
            @if (session('error'))
                <div
                    class="toast align-items-center text-bg-danger border-0"
                    role="alert"
                    aria-live="assertive"
                    aria-atomic="true"
                    data-flash-toast
                    data-bs-autohide="true"
                    data-bs-delay="7000"
                >
                    <div class="d-flex">
                        <div class="toast-body">
                            {{ session('error') }}
                        </div>

                        <button
                            type="button"
                            class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast"
                            aria-label="Đóng thông báo"
                        ></button>
                    </div>
                </div>
            @endif

        </div>
    @endif

    <footer class="bg-white border-top py-3">
        <div class="container text-center text-muted">
            &copy; {{ date('Y') }} LibraryHub — Quản lý thư viện
        </div>
    </footer>
</body>
</html>
