<?php

namespace App\Http\Requests\Log;

use App\Traits\PayloadRuleTrait;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Update extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('logs.update');
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
