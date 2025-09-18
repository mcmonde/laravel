<?php

namespace App\Http\Requests\Upload;

use App\Traits\PayloadRuleTrait;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Update extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('uploads.update');
    }

    public function rules(): array
    {
        return [
            'uploadable_type' => ['required', 'string'],
            'uploadable_id' => ['required', 'string'],
            'type' => ['required', 'string'],
            'original_name' => ['required', 'string'],
            'stored_name' => ['required', 'string'],
            'extensions' => ['required', 'array'],
            'size' => ['required', 'integer', 'min:1'],
            'mime_type' => ['required', 'string'],
            'disk' => ['required', 'string'],
            'path' => ['required', 'string'],
            'url' => ['required', 'string'],
            'meta' => ['nullable', 'array'],
        ];
    }

    // NOTE! Use this codes only in class Index
//    public function messages(): array
//    {
//        return array_merge(parent::messages(), $this->payloadMessages());
//    }
}
