<?php

namespace App\Http\Controllers\Drive;

use App\Actions\Drive\DeleteToTrashAction;
use App\Actions\Drive\MoveFileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Drive\MoveRequest;
use App\Http\Requests\Drive\UpdateFileRequest;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function update(UpdateFileRequest $request, File $file): RedirectResponse
    {
        $name = $request->validated('name');
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        $file->update([
            'name' => $name,
            'extension' => $extension !== '' ? $extension : $file->extension,
        ]);

        return back();
    }

    public function move(MoveRequest $request, File $file, MoveFileAction $move): RedirectResponse
    {
        $newFolder = $request->parent_id
            ? Folder::where('user_id', $request->user()->id)->findOrFail($request->parent_id)
            : null;

        $move->execute($file, $newFolder);

        return back();
    }

    public function destroy(File $file, DeleteToTrashAction $trash): RedirectResponse
    {
        Gate::authorize('delete', $file);

        $trash->execute($file);

        return back();
    }

    public function download(File $file): StreamedResponse
    {
        Gate::authorize('download', $file);

        $version = $file->currentVersion;
        abort_unless($version, 404);

        $file->update(['last_accessed_at' => now()]);

        return Storage::disk($version->disk)->download($version->storage_path, $file->name);
    }

    public function preview(File $file): StreamedResponse
    {
        Gate::authorize('view', $file);

        $version = $file->currentVersion;
        abort_unless($version, 404);

        $file->update(['last_accessed_at' => now()]);

        return Storage::disk($version->disk)->response(
            $version->storage_path,
            $file->name,
            ['Content-Type' => $file->mime_type],
        );
    }
}
