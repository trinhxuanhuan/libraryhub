<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->ignore($category),
            ],
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
