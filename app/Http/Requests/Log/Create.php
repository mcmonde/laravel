<?php

namespace App\Http\Requests\Log;

use App\Traits\PayloadRuleTrait;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Create extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('logs.create');
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
