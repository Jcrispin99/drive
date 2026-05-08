<?php

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('folder'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[^\/\\\\]+$/'],
        ];
    }
}
