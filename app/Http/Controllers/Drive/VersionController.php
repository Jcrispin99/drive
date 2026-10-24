<?php

namespace App\Http\Controllers\Drive;

use App\Actions\Drive\UploadFileAction;
use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\FileVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VersionController extends Controller
{
    public function store(Request $request, File $file, UploadFileAction $upload): RedirectResponse
    {
        Gate::authorize('update', $file);

        $request->validate([
            'file' => ['required', 'file', 'max:'.(config('drive.max_upload_size_mb') * 1024)],
        ], [
            'file.max' => 'El archivo no debe superar '.config('drive.max_upload_size_mb').' MB.',
            'file.uploaded' => 'No se pudo recibir el archivo. Comprueba el límite de carga del servidor e inténtalo de nuevo.',
        ]);

        $upload->addVersion($request->user(), $file, $request->file('file'));

        return back();
    }

    public function restore(Request $request, File $file, FileVersion $version): RedirectResponse
    {
        Gate::authorize('update', $file);

        abort_unless($version->file_id === $file->id, 404);

        $file->update([
            'current_version_id' => $version->id,
            'size' => $version->size,
        ]);

        return back();
    }
}
