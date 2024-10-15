<?php

namespace App\Http\Requests\User;

use App\Rules\ExistsInTable;
use App\Rules\UniqueColumnUpdate;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Update extends FormRequest
{
    public function authorize(): bool
    {
        return Bouncer::can('users.update');
    }

    public function rules(): array
    {
        $id = $this->route('users');

        return [
            'new_password' => 'nullable|min:8',
            'current_password' => 'nullable|min:8',
            'first_name' => ['required'],
            'middle_name' => ['nullable'],
            'last_name' => ['required'],
            'email' => ['required', 'email', new UniqueColumnUpdate('users', $id)],
            'role_id' => ['required', 'array', new ExistsInTable('roles')],
            'address' => ['nullable'],
            'contact_number' => ['nullable', 'numeric'],
            'status' => ['boolean'],
            'allow_login' => ['boolean'],
            'tin' => ['nullable']
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'This field is required',
            'last_name.required' => 'This field is required',
            'email.required' => 'This field is required',
            'password.required' => 'This field is required',
            'role_id.required' => 'This field is required',
        ];
    }
}
