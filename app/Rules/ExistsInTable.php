<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ExistsInTable implements ValidationRule
{
    protected string $table;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = null;

        if (is_array($value)) {
            foreach ($value as $id) {

                if(is_numeric($id))
                    $exists = DB::table($this->table)->where('id', $id)->whereNull('deleted_at')->exists();
                else
                    $fail('This field is required.');

                if (!$exists) {
                    $fail('The :attribute you selected does not exist.');
                }
            }
        } else {
            if(is_numeric($value))
                $exists = DB::table($this->table)->where('id', $value)->whereNull('deleted_at')->exists();
            else
                $fail('This field is required.');

            if (!$exists) {
                $fail('The :attribute you selected does not exist.');
            }
        }
    }
}
