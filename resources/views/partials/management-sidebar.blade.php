<aside class="management-sidebar">
    <div class="management-sidebar-header">
        <span class="small text-uppercase fw-bold text-muted">
            Quản lý thư viện
        </span>
    </div>

    <nav
        class="nav flex-row flex-lg-column flex-nowrap gap-1 management-sidebar-nav"
        aria-label="Menu quản lý"
    >
        <a
            class="nav-link {{ request()->routeIs('management.dashboard') ? 'active' : '' }}"
            href="{{ route('management.dashboard') }}"
        >
            Tổng quan
        </a>

        <a
            class="nav-link {{ request()->routeIs('admin.books.*') ? 'active' : '' }}"
            href="{{ route('admin.books.index') }}"
        >
            Sách
        </a>

        <a
            class="nav-link {{ request()->routeIs('admin.authors.*') ? 'active' : '' }}"
            href="{{ route('admin.authors.index') }}"
        >
            Tác giả
        </a>

        <a
            class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
            href="{{ route('admin.categories.index') }}"
        >
            Thể loại
        </a>

        <a
            class="nav-link {{ request()->routeIs('management.loans.*') ? 'active' : '' }}"
            href="{{ route('management.loans.index') }}"
        >
            Phiếu mượn
        </a>

        @if ($isAdmin)
            <a
                class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                href="{{ route('admin.users.index') }}"
            >
                Người dùng
            </a>
        @endif
    </nav>
</aside>