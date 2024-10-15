<?php

namespace App\Http\Requests;

use App\Traits\PayloadRuleTrait;
use Bouncer;
use Illuminate\Foundation\Http\FormRequest;

class StoreLogRequest extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('{{ ability }}');
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
