<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueColumnStore implements ValidationRule
{
    protected string $table;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (DB::table($this->table)->where($attribute,'ilike',$value)->whereNull('deleted_at')->exists())
            $fail(ucfirst($attribute).' already taken.');

    }
}
