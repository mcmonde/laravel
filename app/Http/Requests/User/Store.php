<?php

namespace App\Http\Requests\User;

use App\Rules\ExistsInTable;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Store extends FormRequest
{
    public function authorize(): bool
    {
        return Bouncer::can('users.store');
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required'],
            'middle_name' => ['nullable'],
            'last_name' => ['required'],
            'email' => 'required|unique:users|email',
            'password' => 'required|min:8',
            'role_id' => ['required', 'array', new ExistsInTable('roles')],
            'address' => ['nullable'],
            'contact_number' => ['nullable', 'numeric', 'regex:/^9\d{9}$/'],
            'status' => ['boolean'],
            'allow_login' => ['boolean'],
            'created_by' => ['nullable'],
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
            'contact_number.regex' => 'Should be this format (+63 9** *** ****)',
        ];
    }

    public function validationData(): array
    {
        $this->merge(['created_by' => auth()->user()->id]);
        return $this->all();
    }
}
