<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class ValueExistsInTable implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    protected $table;
    protected $column;
    protected $id;

     public function __construct($table, $column, $id)
     {
         $this->table = $table;
         $this->column = $column;
         $this->id = $id;
     }
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if(DB::table($this->table)->where($this->column, $this->id)->exists()){
            $fail('Error. This id is an instance of '.$this->table);
        }
    }
}
