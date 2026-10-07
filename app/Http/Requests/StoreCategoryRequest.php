<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {

        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên thể loại.',
            'name.string' => 'Tên thể loại phải là văn bản.',
            'name.max' => 'Tên thể loại không được quá 100 ký tự.',
            'name.unique' => 'Tên thể loại đã tồn tại.',
            'description.string' => 'Mô tả phải là văn bản.',
        ];
    }
}
