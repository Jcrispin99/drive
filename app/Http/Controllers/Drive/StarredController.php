<?php

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StarredController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $folders = Folder::where('user_id', $user->id)
            ->whereNotNull('starred_at')
            ->orderBy('name')
            ->get()
            ->map(fn (Folder $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'path' => $f->path,
                'starred' => true,
            ]);

        $files = File::where('user_id', $user->id)
            ->whereNotNull('starred_at')
            ->orderBy('name')
            ->get()
            ->map(fn (File $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'extension' => $f->extension,
                'mime_type' => $f->mime_type,
                'size' => $f->size,
                'starred' => true,
            ]);

        return Inertia::render('drive/Starred', [
            'folders' => $folders,
            'files' => $files,
        ]);
    }
}
