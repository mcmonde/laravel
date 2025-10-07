<?php

namespace App\Http\Requests\StorageType;

use App\Rules\UniqueColumnStore;
use App\Traits\PayloadRuleTrait;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Store extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('storage-types.store');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', new UniqueColumnStore('storage_types')],
            'driver' => ['required'],
            'config' => ['nullable', 'array'],
        ];
    }
}
