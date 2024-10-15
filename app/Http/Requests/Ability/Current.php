<?php

namespace App\Http\Requests\Ability;

use App\Models\Role;
use App\Traits\PayloadRuleTrait;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Current extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('abilities.current');
    }

    public function rules(): array
    {
        $additional_rules = [
            'role_name' => ['required', function ($attribute, $value, $fail) {
                $exists = Role::where('name', $value)->whereNull('deleted_at')->exists();

                if (!$exists) {
                    $fail('The :attribute ' . $value . ' does not exist.');
                }
            }]
        ];

        return array_merge($this->payloadRules(), $additional_rules);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), $this->payloadMessages());
    }
}
