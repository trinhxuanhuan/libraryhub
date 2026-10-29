<?php

declare(strict_types=1);

return [
    'categories' => [
        'created' => 'Thể loại đã được tạo thành công.',
        'updated' => 'Thể loại đã được cập nhật thành công.',
        'deleted' => 'Thể loại đã được xóa thành công.',
        'delete_blocked' => 'Không thể xóa thể loại vì có sách liên quan.',
    ],

    'authors' => [
        'created' => 'Đã thêm tác giả thành công.',
        'updated' => 'Đã cập nhật tác giả thành công.',
        'deleted' => 'Đã xóa tác giả thành công.',
        'delete_blocked' => 'Không thể xóa tác giả vì có sách liên quan.',
    ],

    'books' => [
        'created' => 'Thêm sách thành công.',
        'updated' => 'Cập nhật sách thành công.',
        'save_failed' => 'Không thể lưu thông tin sách. Vui lòng thử lại.',
        'deleted' => 'Xóa sách thành công.',
        'quantity_out_of_sync' => 'Số lượng sách đang không đồng bộ.',
        'decrease_copies_not_supported' => 'Chưa hỗ trợ giảm số bản sách.',
        'retired' => 'Đã ngừng cung cấp đầu sách. Có :count phiếu chờ được từ chối.',
    ],

    'auth' => [
        'registered' => 'Đăng ký tài khoản thành công.',
        'logged_in' => 'Đăng nhập thành công.',
        'failed' => 'Email hoặc mật khẩu không chính xác.',
        'logged_out' => 'Đăng xuất thành công.',
        'inactive' => 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.',
    ],
    'users' => [
        'updated' => 'Cập nhật người dùng thành công.',
        'cannot_self_demote' => 'Bạn không thể tự hạ quyền Admin.',
        'cannot_self_deactivate' => 'Bạn không thể tự khóa tài khoản.',
    ],

    'authorization' => [
        'forbidden' => 'Bạn không có quyền truy cập chức năng này.',
    ],
    'loans' => [
        'request_created' => 'Đã gửi yêu cầu mượn sách. Vui lòng chờ thủ thư xác nhận.',
        'books_unavailable' => 'Một hoặc nhiều đầu sách không còn bản khả dụng. Vui lòng chọn lại.',
        'selection_added' => 'Đã thêm sách vào danh sách muốn mượn.',
        'selection_removed' => 'Đã bỏ sách khỏi danh sách muốn mượn.',
        'selection_empty' => 'Vui lòng chọn ít nhất một cuốn sách.',
        'already_borrowing' => 'Bạn đã có yêu cầu đang chờ duyệt hoặc đang mượn một trong các đầu sách đã chọn.',
        'duplicate_physical_copy' => 'Bạn đang chọn trùng mã barcode. Mỗi bản sách vật lý chỉ được giao một lần.',
        'rejected_by_staff' => 'Yêu cầu mượn đã bị thư viện từ chối.',
        'rejected_book_retired' => 'Thư viện đã ngừng cung cấp đầu sách ":book". Phiếu mượn đã bị từ chối. Vui lòng gửi yêu cầu mới nếu muốn mượn các sách khác.',
        'invalid_date_range' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi.',
        'renewed' => 'Gia hạn phiếu mượn thành công.',
        'issued' => 'Đã duyệt và giao sách thành công.',
        'rejected' => 'Đã từ chối yêu cầu mượn sách.',
        'item_processed' => 'Đã ghi nhận xử lý bản sách thành công.',
        'fine_paid' => 'Đã xác nhận thu tiền phạt thành công.',
        'created' => 'Đã tạo phiếu mượn. Hãy chọn bản sách để giao.',
    ],

    'profile' => [
        'updated' => 'Cập nhật hồ sơ thành công.',
        'password_changed' => 'Đổi mật khẩu thành công.',
        'invalid_current_password' => 'Mật khẩu hiện tại không đúng.',

        'otp_sent' => 'Mã xác thực đã được gửi đến email của bạn.',
        'otp_resent' => 'Mã xác thực mới đã được gửi đến email của bạn.',

        'otp_request_expired' => 'Yêu cầu đổi mật khẩu đã hết hạn. Vui lòng bắt đầu lại.',
        'otp_expired' => 'Mã xác thực không tồn tại hoặc đã hết hạn.',
        'otp_invalid' => 'Mã xác thực không đúng.',
        'otp_locked' => 'Bạn đã nhập sai quá số lần cho phép. Vui lòng bắt đầu lại.',

        'otp_send_limit' => 'Bạn đã yêu cầu quá nhiều mã xác thực. Vui lòng thử lại sau.',
        'otp_cooldown' => 'Vui lòng đợi 60 giây trước khi yêu cầu mã mới.',
        'otp_send_failed' => 'Không thể gửi mã xác thực. Vui lòng thử lại.',
        'otp_busy' => 'Đang xử lý một yêu cầu khác. Vui lòng thử lại.',
    ],
];
