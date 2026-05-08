<?php

namespace App\Http\Requests\Drive;

use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Folder::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[^\/\\\\]+$/'],
            'parent_id' => [
                'nullable',
                Rule::exists('folders', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'El nombre no puede contener / o \\.',
        ];
    }
}
