<?php

namespace App\Http\Requests\StorageType;

use App\Rules\UniqueColumnUpdate;
use App\Traits\PayloadRuleTrait;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Update extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('storage-types.update');
    }

    public function rules(): array
    {
        $id = $this->route('storage_types');
        return [
            'name' => ['required', 'string', new UniqueColumnUpdate('storage_types', $id)],
            'driver' => ['required'],
            'config' => ['nullable', 'array'],
        ];
    }
}
