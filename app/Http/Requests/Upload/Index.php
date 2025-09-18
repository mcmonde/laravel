<?php

namespace App\Http\Requests\Upload;

use App\Traits\PayloadRuleTrait;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Index extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('uploads.index');
    }

    public function rules(): array
    {
        $additional_rules = [
            // add additional rules here.
        ];
        return array_merge($this->payloadRules(), $additional_rules);
    }

    // NOTE! Use this codes only in class Index
//    public function messages(): array
//    {
//        return array_merge(parent::messages(), $this->payloadMessages());
//    }
}
