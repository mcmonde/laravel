<?php

namespace App\Http\Requests\Ability;

use App\Rules\UniqueColumnUpdate;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Update extends FormRequest
{
    public function authorize(): bool
    {
        return Bouncer::can('abilities.update');
    }

    public function rules(): array
    {
        $id = $this->route('abilities');

        return [
            'title' => ['required', new UniqueColumnUpdate('abilities',$id)]
        ];
    }
}
