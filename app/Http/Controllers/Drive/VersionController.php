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
            'file' => ['required', 'file', 'max:102400'],
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
