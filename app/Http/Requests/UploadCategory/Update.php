<?php

namespace App\Http\Requests\UploadCategory;

use App\Rules\UniqueColumnUpdate;
use App\Traits\PayloadRuleTrait;
use Illuminate\Foundation\Http\FormRequest;
use Bouncer;

class Update extends FormRequest
{
    use PayloadRuleTrait;

    public function authorize(): bool
    {
        return Bouncer::can('upload-categories.update');
    }

    public function rules(): array
    {
        $id = $this->route('upload_categories');
        return [
            'name' => ['required', 'string', new UniqueColumnUpdate('upload_categories', $id)],
        ];
    }
}
