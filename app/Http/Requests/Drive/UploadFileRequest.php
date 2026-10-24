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
            'file' => ['required', 'file', 'max:'.(config('drive.max_upload_size_mb') * 1024)],
            'parent_id' => [
                'nullable',
                Rule::exists('folders', 'id')->where('user_id', $this->user()->id),
            ],
            'relative_path' => ['nullable', 'string', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'El archivo no debe superar '.config('drive.max_upload_size_mb').' MB.',
            'file.uploaded' => 'No se pudo recibir el archivo. Comprueba el límite de carga del servidor e inténtalo de nuevo.',
        ];
    }
}
