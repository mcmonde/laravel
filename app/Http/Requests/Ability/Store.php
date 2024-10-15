<?php

namespace App\Http\Requests\Ability;

use App\Models\Ability;
use App\Rules\UniqueColumnStore;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Store extends FormRequest
{
    public function authorize(): bool
    {
        return Bouncer::can('abilities.store');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', new UniqueColumnStore('abilities'),function ($attribute, $value, $fail) {
                $pattern = '/^[a-z0-9]+(?:-[a-z0-9]+)*\.[a-z0-9]+(?:-[a-z0-9]+)*[^-.]$/';

                if(!preg_match($pattern, $value))
                    $fail('You submitted a wrong ability name pattern. Please input again');

                $ability = explode('.', $value);

                if(!Ability::where('name', 'like', "%$ability[0].%")->first())
                    $fail('Ability did not match any in the database. adding failed.');
            }],
            'title' => ['required', new UniqueColumnStore('abilities')]
        ];
    }

    public function validationData(): array
    {
        $this->merge(['name' => preg_replace('/\s+/', '-', strtolower(trim($this->input('name'))))]);
        return $this->all();
    }
}
