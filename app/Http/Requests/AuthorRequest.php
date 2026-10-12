<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'bio' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên tác giả.',
            'name.string' => 'Tên tác giả phải là văn bản.',
            'name.max' => 'Tên tác giả không được vượt quá 150 ký tự.',
            'bio.string' => 'Tiểu sử phải là văn bản.',
        ];
    }
}
