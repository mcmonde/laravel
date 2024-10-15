<?php

namespace App\Http\Requests\Role;

use App\Rules\UniqueColumnStore;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Store extends FormRequest
{

    public function authorize(): bool
    {
        return Bouncer::can('roles.store');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', new UniqueColumnStore('roles')],
            'title' => ['required', new UniqueColumnStore('roles')]
        ];
    }

    public function validationData(): array
    {
        $this->merge(['name' => preg_replace('/\s+/', '-', strtolower(trim($this->input('name'))))]);
        return $this->all();
    }
}
