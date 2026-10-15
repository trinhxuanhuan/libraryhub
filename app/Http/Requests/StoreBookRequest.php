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

            'publisher' => ['required', 'string', 'max:150'],

            'published_year' => [
                'required',
                'integer',
                'between:1800,'.date('Y'),
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
}
