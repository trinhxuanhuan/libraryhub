@extends('layouts.app')

@section('title', 'Giới thiệu — LibraryHub')

@section('content')
    <section class="lh-about-hero rounded-4 overflow-hidden p-4 p-lg-5 mb-4" aria-labelledby="about-heading">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="lh-eyebrow text-primary mb-3">VỀ LIBRARYHUB</span>

                <h1 id="about-heading" class="display-6 fw-bold lh-heading-tight mb-3">
                    Nơi tri thức được tìm thấy<br class="d-none d-md-block">
                    và sẻ chia.
                </h1>

                <p class="lead text-secondary mb-4">
                    LibraryHub là hệ thống hỗ trợ tra cứu tài liệu và quản lý
                    việc mượn, trả sách. Chúng tôi hướng tới trải nghiệm đơn giản
                    cho người đọc và quy trình rõ ràng cho người quản lý thư viện.
                </p>

                <a href="{{ route('books.index') }}" class="btn btn-primary btn-lg px-4">
                    Khám phá danh mục sách <span aria-hidden="true">&rarr;</span>
                </a>
            </div>

            <div class="col-lg-4 d-none d-lg-block" aria-hidden="true">
                <div class="lh-about-art">
                    <div class="lh-about-art-circle"></div>
                    <div class="lh-about-art-book lh-about-art-book-first"></div>
                    <div class="lh-about-art-book lh-about-art-book-second"></div>
                    <div class="lh-about-art-book lh-about-art-book-third"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5" aria-labelledby="about-values-heading">
        <div class="text-center mb-4">
            <span class="lh-eyebrow text-primary mb-2">NHỮNG GIÁ TRỊ CỐT LÕI</span>
            <h2 id="about-values-heading" class="h2 fw-bold mb-0">Một thư viện gần gũi hơn</h2>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <article class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="lh-feature-icon lh-feature-blue mb-3" aria-hidden="true">01</div>
                        <h3 class="h5 fw-bold">Dễ tiếp cận</h3>
                        <p class="text-muted mb-0">
                            Bất kỳ ai cũng có thể xem danh mục và tìm thông tin sách,
                            ngay cả khi chưa đăng nhập.
                        </p>
                    </div>
                </article>
            </div>

            <div class="col-md-4">
                <article class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="lh-feature-icon lh-feature-teal mb-3" aria-hidden="true">02</div>
                        <h3 class="h5 fw-bold">Minh bạch</h3>
                        <p class="text-muted mb-0">
                            Thành viên có thể theo dõi phiếu mượn và hạn trả;
                            thủ thư quản lý quá trình duyệt, giao và nhận sách.
                        </p>
                    </div>
                </article>
            </div>

            <div class="col-md-4">
                <article class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="lh-feature-icon lh-feature-orange mb-3" aria-hidden="true">03</div>
                        <h3 class="h5 fw-bold">Kết nối</h3>
                        <p class="text-muted mb-0">
                            Một không gian chung cho người đọc, thủ thư và quản trị viên
                            cùng sử dụng, mỗi vai trò có chức năng phù hợp.
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm rounded-4 mb-4" aria-labelledby="about-process-heading">
        <div class="card-body p-4 p-lg-5">
            <div class="row g-4">
                <div class="col-lg-4">
                    <span class="lh-eyebrow text-primary mb-2">QUY TRÌNH MƯỢN SÁCH</span>
                    <h2 id="about-process-heading" class="h2 fw-bold mb-3">Từ tìm sách đến nhận sách</h2>
                    <p class="text-muted mb-0">
                        Bạn có thể tra cứu tự do. Khi muốn mượn sách,
                        hãy đăng nhập tài khoản thành viên và gửi yêu cầu để thủ thư xử lý.
                    </p>
                </div>

                <div class="col-lg-8">
                    <ol class="lh-process-list mb-0">
                        <li class="lh-process-item">
                            <span class="lh-process-index" aria-hidden="true">1</span>
                            <div>
                                <h3 class="h6 fw-bold mb-1">Tìm cuốn sách phù hợp</h3>
                                <p class="text-muted mb-0">Xem danh mục, tìm kiếm và kiểm tra số bản sách còn sẵn.</p>
                            </div>
                        </li>

                        <li class="lh-process-item">
                            <span class="lh-process-index" aria-hidden="true">2</span>
                            <div>
                                <h3 class="h6 fw-bold mb-1">Chọn sách và gửi yêu cầu</h3>
                                <p class="text-muted mb-0">Đăng nhập, thêm sách vào danh sách chọn và gửi phiếu yêu cầu mượn.</p>
                            </div>
                        </li>

                        <li class="lh-process-item">
                            <span class="lh-process-index" aria-hidden="true">3</span>
                            <div>
                                <h3 class="h6 fw-bold mb-1">Thủ thư duyệt và giao sách</h3>
                                <p class="text-muted mb-0">Thủ thư xác nhận yêu cầu và ghi nhận việc giao sách.</p>
                            </div>
                        </li>

                        <li class="lh-process-item">
                            <span class="lh-process-index" aria-hidden="true">4</span>
                            <div>
                                <h3 class="h6 fw-bold mb-1">Theo dõi và trả đúng hạn</h3>
                                <p class="text-muted mb-0">Xem tình trạng phiếu mượn, hạn trả và yêu cầu gia hạn khi đủ điều kiện.</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="lh-cta-strip rounded-4 p-4 p-lg-5 text-center" aria-labelledby="about-cta-heading">
        <h2 id="about-cta-heading" class="h3 fw-bold mb-2">Cuốn sách tiếp theo đang chờ bạn</h2>
        <p class="text-muted mb-4">Bắt đầu từ danh mục sách và chọn một cuốn bạn thật sự muốn đọc.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a class="btn btn-primary px-4" href="{{ route('books.index') }}">Xem danh mục sách</a>
            <a class="btn btn-outline-primary px-4" href="{{ route('home') }}">Về trang chủ</a>
        </div>
    </section>
@endsection
