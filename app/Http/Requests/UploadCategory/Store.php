<?php

namespace App\Http\Requests\UploadCategory;

use App\Rules\UniqueColumnStore;
use App\Traits\PayloadRuleTrait;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Store extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('upload-categories.store');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', new UniqueColumnStore('upload_categories')],
            'description' => ['nullable', 'string'],
        ];
    }
}
