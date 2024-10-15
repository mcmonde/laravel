<?php

namespace App\Http\Requests\Log;

use App\Traits\PayloadRuleTrait;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Index extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('logs.index');
    }

    public function rules(): array
    {
        $additional_rules = [
            // custom rules here.
        ];

        return array_merge($this->payloadRules(), $additional_rules);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), $this->payloadMessages());
    }
}
