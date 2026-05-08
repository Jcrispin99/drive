<?php

namespace App\Http\Controllers\Drive;

use App\Actions\Drive\EnsureFolderPathAction;
use App\Actions\Drive\UploadFileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Drive\UploadFileRequest;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    public function store(
        UploadFileRequest $request,
        EnsureFolderPathAction $ensurePath,
        UploadFileAction $upload,
    ): JsonResponse {
        $user = $request->user();

        $root = $request->parent_id
            ? Folder::where('user_id', $user->id)->findOrFail($request->parent_id)
            : null;

        $relativePath = $request->input('relative_path');
        $directory = $relativePath
            ? trim(dirname($relativePath), '.')
            : '';

        $targetFolder = $directory !== '' && $directory !== '/'
            ? $ensurePath->execute($user, $root, $directory)
            : $root;

        $file = $upload->execute($user, $request->file('file'), $targetFolder);

        return response()->json([
            'file' => [
                'id' => $file->id,
                'name' => $file->name,
                'folder_id' => $file->folder_id,
            ],
        ]);
    }
}
