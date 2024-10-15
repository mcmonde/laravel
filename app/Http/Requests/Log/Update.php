<?php

namespace App\Http\Requests\Log;

use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Update extends FormRequest
{
    public function authorize(): bool
    {
        return Bouncer::can('logs.update');
    }

    public function rules(): array
    {
        return [
            // custom rules here.
        ];
    }
}
