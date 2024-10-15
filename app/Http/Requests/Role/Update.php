<?php

namespace App\Http\Requests\Role;

use App\Rules\UniqueColumnUpdate;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Update extends FormRequest
{
    public function authorize(): bool
    {
        return Bouncer::can('roles.update');
    }

    public function rules(): array
    {
        $id = $this->route('roles');
        return [
            'title' => ['required', new UniqueColumnUpdate('roles',$id)]
        ];
    }
}
