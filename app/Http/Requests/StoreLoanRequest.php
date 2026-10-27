<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_ids' => ['required', 'array', 'min:1'],

            'book_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('books', 'id')
                    ->whereNull('deleted_at'),
            ],
        ];
    }
}
