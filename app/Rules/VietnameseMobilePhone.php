<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VietnameseMobilePhone implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        if (
            ! is_string($value)
            || preg_match('/^0[35789][0-9]{8}$/D', $value) !== 1
        ) {
            $fail(
                'Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 03, 05, 07, 08 hoặc 09.'
            );
        }
    }
}
