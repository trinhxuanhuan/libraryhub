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
];
