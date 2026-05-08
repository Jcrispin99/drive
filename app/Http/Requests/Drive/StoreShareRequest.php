<?php

namespace App\Http\Requests\Drive;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'shareable_type' => ['required', Rule::in(['file', 'folder'])],
            'shareable_id' => ['required', 'integer'],
            'shared_with_email' => ['nullable', 'email', 'exists:users,email'],
            'public_link' => ['boolean'],
            'permission' => ['required', Rule::in(['view', 'edit'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function shareableClass(): string
    {
        return $this->shareable_type === 'folder' ? Folder::class : File::class;
    }
}
