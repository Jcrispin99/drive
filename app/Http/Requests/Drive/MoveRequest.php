<?php

namespace App\Http\Requests\Drive;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('folder') ?? $this->route('file');

        return $target && $this->user()->can('update', $target);
    }

    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                Rule::exists('folders', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
