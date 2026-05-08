<?php

namespace App\Http\Requests\Drive;

use App\Models\File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', File::class);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:102400'],
            'parent_id' => [
                'nullable',
                Rule::exists('folders', 'id')->where('user_id', $this->user()->id),
            ],
            'relative_path' => ['nullable', 'string', 'max:1024'],
        ];
    }
}
