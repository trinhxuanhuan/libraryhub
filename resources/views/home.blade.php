@extends('layouts.app')

@section('title', 'Trang chủ — LibraryHub')

@section('content')
    <section class="lh-hero rounded-4 shadow-sm overflow-hidden mb-4" aria-labelledby="home-heading">
        <div class="row align-items-center g-4 px-4 py-5 p-lg-5">
            <div class="col-lg-7 position-relative lh-hero-content">
                <span class="lh-eyebrow lh-eyebrow-light mb-3">CHÀO MỪNG ĐẾN LIBRARYHUB</span>

                <h1 id="home-heading" class="display-5 fw-bold lh-heading-tight mb-3">
                    Mở trang sách mới.<br>
                    Khám phá điều tuyệt vời.
                </h1>

                <p class="lh-hero-description mb-4">
                    Tìm cuốn sách bạn yêu thích, gửi yêu cầu mượn và theo dõi
                    hành trình đọc sách — tất cả tại một nơi.
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-light btn-lg text-primary fw-semibold px-4" href="{{ route('books.index') }}">
                        Khám phá danh mục sách
                        <span aria-hidden="true">&rarr;</span>
                    </a>

                    <a class="btn btn-outline-light btn-lg px-4" href="{{ route('about') }}">
                        Tìm hiểu thêm
                    </a>
                </div>
            </div>

            {{-- Hình minh họa thuần CSS, không phụ thuộc ảnh bên ngoài. --}}
            <div class="col-lg-5 d-none d-lg-block" aria-hidden="true">
                <div class="lh-book-scene">
                    <div class="lh-book lh-book-one">
                        <span>KHÁM PHÁ</span>
                        <strong>Ý tưởng</strong>
                    </div>
                    <div class="lh-book lh-book-two">
                        <span>MỞ RỘNG</span>
                        <strong>Tri thức</strong>
                    </div>
                    <div class="lh-book lh-book-three">
                        <span>NUÔI DƯỠNG</span>
                        <strong>Cảm hứng</strong>
                    </div>
                    <div class="lh-book-shelf"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm rounded-4 mb-5" aria-labelledby="quick-search-heading">
        <div class="card-body p-4 p-lg-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-4">
                    <h2 id="quick-search-heading" class="h5 fw-bold mb-1">Bạn đang tìm cuốn sách nào?</h2>
                    <p class="text-muted small mb-0">Tìm theo tên sách, tác giả hoặc ISBN.</p>
                </div>

                <div class="col-lg-8">
                    <form action="{{ route('books.index') }}" method="GET" role="search">
                        <label class="visually-hidden" for="home-book-search">Tìm sách</label>
                        <div class="input-group input-group-lg">
                            <input
                                id="home-book-search"
                                class="form-control lh-search-input"
                                type="search"
                                name="q"
                                maxlength="150"
                                placeholder="Ví dụ: Clean Code, Nguyễn Nhật Ánh..."
                            >
                            <button class="btn btn-primary px-4" type="submit">Tìm sách</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5" aria-labelledby="features-heading">
        <div class="text-center mb-4">
            <span class="lh-eyebrow text-primary mb-2">MỌI THỨ BẠN CẦN</span>
            <h2 id="features-heading" class="h2 fw-bold mb-2">Đọc sách dễ dàng hơn mỗi ngày</h2>
            <p class="text-muted mb-0">LibraryHub kết nối người đọc với thư viện một cách đơn giản và rõ ràng.</p>
        </div>

        <div class="row g-3 g-lg-4">
            <div class="col-md-4">
                <article class="card lh-feature-card h-100 border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="lh-feature-icon lh-feature-blue mb-3" aria-hidden="true">01</div>
                        <h3 class="h5 fw-bold">Tra cứu nhanh chóng</h3>
                        <p class="text-muted mb-0">
                            Tìm kiếm theo tên sách, tác giả, ISBN; lọc theo thể loại
                            và xem tình trạng sách trước khi mượn.
                        </p>
                    </div>
                </article>
            </div>

            <div class="col-md-4">
                <article class="card lh-feature-card h-100 border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="lh-feature-icon lh-feature-teal mb-3" aria-hidden="true">02</div>
                        <h3 class="h5 fw-bold">Yêu cầu mượn tiện lợi</h3>
                        <p class="text-muted mb-0">
                            Chọn sách trực tuyến, gửi yêu cầu và chờ thủ thư
                            xác nhận trước khi nhận sách.
                        </p>
                    </div>
                </article>
            </div>

            <div class="col-md-4">
                <article class="card lh-feature-card h-100 border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="lh-feature-icon lh-feature-orange mb-3" aria-hidden="true">03</div>
                        <h3 class="h5 fw-bold">Theo dõi dễ dàng</h3>
                        <p class="text-muted mb-0">
                            Xem các phiếu mượn, theo dõi hạn trả và chủ động
                            gia hạn khi phiếu mượn đủ điều kiện.
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="lh-surface rounded-4 border p-4 p-lg-5 mb-4" aria-labelledby="how-it-works-heading">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div>
                <span class="lh-eyebrow text-primary mb-2">BẮT ĐẦU THẬT ĐƠN GIẢN</span>
                <h2 id="how-it-works-heading" class="h2 fw-bold mb-0">Ba bước để đến gần hơn với sách</h2>
            </div>
            <a class="link-primary fw-semibold text-decoration-none" href="{{ route('about') }}">
                Xem quy trình chi tiết <span aria-hidden="true">&rarr;</span>
            </a>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="lh-step-number mb-3">1</div>
                <h3 class="h6 fw-bold">Khám phá sách</h3>
                <p class="text-muted mb-0">Duyệt danh mục và chọn đầu sách phù hợp với bạn.</p>
            </div>
            <div class="col-md-4">
                <div class="lh-step-number mb-3">2</div>
                <h3 class="h6 fw-bold">Gửi yêu cầu</h3>
                <p class="text-muted mb-0">Đăng nhập tài khoản thành viên để gửi danh sách muốn mượn.</p>
            </div>
            <div class="col-md-4">
                <div class="lh-step-number mb-3">3</div>
                <h3 class="h6 fw-bold">Nhận và theo dõi</h3>
                <p class="text-muted mb-0">Sau khi thủ thư duyệt và giao sách, theo dõi phiếu mượn tại tài khoản.</p>
            </div>
        </div>
    </section>
@endsection
