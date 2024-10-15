<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMonth implements ValidationRule
{

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!in_array(strtolower($value), ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december']))
            $fail('The :attribute must be a valid month.');
    }
}
