<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateBookRequest extends StoreBookRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $book = $this->route('book');

        $rules['isbn'] = [
            'required',
            'string',
            'max:20',
            Rule::unique('books', 'isbn')->ignore($book),
        ];

        return $rules;
    }
}
