<?php

declare(strict_types=1);

return [
    // Thông báo dùng chung
    'required' => 'Vui lòng nhập :attribute.',
    'string' => ':attribute phải là văn bản.',
    'integer' => ':attribute phải là số nguyên.',
    'array' => ':attribute phải là một danh sách.',
    'exists' => ':attribute không tồn tại.',
    'unique' => ':attribute đã tồn tại.',
    'distinct' => ':attribute không được trùng lặp.',
    'image' => ':attribute phải là hình ảnh.',
    'mimes' => ':attribute phải có định dạng: :values.',

    'max' => [
        'string' => ':attribute không được quá :max ký tự.',
        'file' => ':attribute không được lớn hơn :max KB.',
    ],

    'min' => [
        'numeric' => ':attribute không được nhỏ hơn :min.',
        'array' => ':attribute phải có ít nhất :min phần tử.',
    ],

    'between' => [
        'numeric' => ':attribute phải nằm trong khoảng :min đến :max.',
    ],

    'lte' => [
        'numeric' => ':attribute không được lớn hơn :value.',
    ],

    // Tên các trường hiển thị cho người dùng
    'attributes' => [
        'category_id' => 'Thể loại',
        'isbn' => 'ISBN',
        'title' => 'Tên sách',
        'description' => 'Mô tả',
        'publisher' => 'Nhà xuất bản',
        'published_year' => 'Năm xuất bản',
        'total_copies' => 'Tổng số bản sách',
        'available_copies' => 'Số bản còn lại',
        'author_ids' => 'Tác giả',
        'author_ids.*' => 'Tác giả được chọn',
        'cover' => 'Ảnh bìa sách',
    ],

    // Những thông báo cần diễn đạt riêng
    'custom' => [
        'category_id' => [
            'required' => 'Vui lòng chọn thể loại.',
        ],

        'published_year' => [
            'between' => 'Năm xuất bản không hợp lệ.',
        ],

        'available_copies' => [
            'lte' => 'Số bản còn lại không được lớn hơn tổng số bản.',
        ],

        'author_ids' => [
            'required' => 'Vui lòng chọn ít nhất một tác giả.',
            'min' => 'Vui lòng chọn ít nhất một tác giả.',
        ],

        'author_ids.*' => [
            'exists' => 'Tác giả được chọn không tồn tại.',
            'distinct' => 'Không được chọn trùng tác giả.',
        ],
    ],
];
