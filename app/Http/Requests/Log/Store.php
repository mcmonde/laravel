<?php

namespace App\Http\Requests\Log;

use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Store extends FormRequest
{
    public function authorize(): bool
    {
        return Bouncer::can('logs.store');
    }

    public function rules(): array
    {
        return [
            // custom rules here.
        ];
    }
}
