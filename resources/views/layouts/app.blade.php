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
        $currentUser = app(\App\Services\ManualAuthenticator::class)
            ->user(request());
    @endphp

    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('home') }}">
                LibraryHub
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
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <a
                        class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                        href="{{ route('home') }}"
                    >
                        Trang chủ
                    </a>

                    <a
                        class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}"
                        href="{{ route('about') }}"
                    >
                        Giới thiệu
                    </a>

                    @if ($currentUser === null)
                        <a
                            class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}"
                            href="{{ route('login') }}"
                        >
                            Đăng nhập
                        </a>

                        <a
                            class="btn btn-primary"
                            href="{{ route('register') }}"
                        >
                            Đăng ký
                        </a>
                    @else
                        @if ($currentUser->role === \App\Enums\UserRole::Admin)
                            <a
                                class="nav-link"
                                href="{{ route('admin.books.index') }}"
                            >
                                Sách
                            </a>

                            <a
                                class="nav-link"
                                href="{{ route('admin.authors.index') }}"
                            >
                                Tác giả
                            </a>

                            <a
                                class="nav-link"
                                href="{{ route('admin.categories.index') }}"
                            >
                                Thể loại
                            </a>

                            <a
                                class="nav-link"
                                href="{{ route('admin.users.index') }}"
                            >
                                Người dùng
                            </a>
                        @endif

                        <span class="navbar-text">
                            {{ $currentUser->name }}
                        </span>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf

                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                            >
                                Đăng xuất
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 flex-grow-1">
        @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-top py-3">
        <div class="container text-center text-muted">
            &copy; {{ date('Y') }} LibraryHub — Quản lý thư viện
        </div>
    </footer>
</body>
</html>
