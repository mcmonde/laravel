<?php

namespace App\Http\Requests\Upload;

use App\Traits\PayloadRuleTrait;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Show extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('uploads.show');
    }

    public function rules(): array
    {
        return [];

        // NOTE! Use this codes only in class Index
//        $additional_rules = [];
//        return array_merge($this->payloadRules(), $additional_rules);
    }

    // NOTE! Use this codes only in class Index
//    public function messages(): array
//    {
//        return array_merge(parent::messages(), $this->payloadMessages());
//    }
}
