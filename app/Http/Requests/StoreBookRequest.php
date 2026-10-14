<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id'),
            ],

            'isbn' => [
                'required',
                'string',
                'max:20',
                Rule::unique('books', 'isbn'),
            ],

            'title' => ['required', 'string', 'max:255'],

            'description' => ['nullable', 'string'],

            'publisher' => ['nullable', 'string', 'max:150'],

            'published_year' => [
                'required',
                'integer',
                'between:1,'.date('Y'),
            ],

            'total_copies' => ['required', 'integer', 'min:0'],

            'available_copies' => [
                'required',
                'integer',
                'min:0',
                'lte:total_copies',
            ],

            'author_ids' => ['required', 'array', 'min:1'],

            'author_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('authors', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Vui lòng chọn thể loại.',
            'category_id.exists' => 'Thể loại không tồn tại.',

            'isbn.required' => 'Vui lòng nhập ISBN.',
            'isbn.unique' => 'ISBN đã tồn tại.',
            'isbn.max' => 'ISBN không được vượt quá 20 ký tự.',

            'title.required' => 'Vui lòng nhập tên sách.',
            'title.max' => 'Tên sách không được vượt quá 255 ký tự.',

            'published_year.required' => 'Vui lòng nhập năm xuất bản.',
            'published_year.between' => 'Năm xuất bản không hợp lệ.',

            'total_copies.required' => 'Vui lòng nhập tổng số bản sách.',
            'total_copies.min' => 'Tổng số bản sách không được âm.',

            'available_copies.required' => 'Vui lòng nhập số bản còn lại.',
            'available_copies.min' => 'Số bản còn lại không được âm.',
            'available_copies.lte' => 'Số bản còn lại không được lớn hơn tổng số bản.',

            'author_ids.required' => 'Vui lòng chọn ít nhất một tác giả.',
            'author_ids.min' => 'Vui lòng chọn ít nhất một tác giả.',
            'author_ids.*.exists' => 'Tác giả được chọn không tồn tại.',
            'author_ids.*.distinct' => 'Không được chọn trùng tác giả.',
        ];
    }
}
