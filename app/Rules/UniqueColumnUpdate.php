<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueColumnUpdate implements ValidationRule
{
    protected string $table;
    protected string $id;
    public function __construct(string $table, string $id)
    {
        $this->table = $table;
        $this->id = $id;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (DB::table($this->table)->where('id',$this->id)->whereNull('deleted_at')->exists()
            && DB::table($this->table)->where($attribute, 'ilike', $value)->whereNot('id',$this->id)->whereNull('deleted_at')->exists())
            $fail(ucfirst($attribute).' already taken.');
    }
}
