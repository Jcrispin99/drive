<?php

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $request->validate(['q' => ['nullable', 'string', 'max:255']]);

        $user = $request->user();
        $q = trim((string) $request->input('q', ''));

        $folders = collect();
        $files = collect();

        if ($q !== '') {
            $folders = Folder::where('user_id', $user->id)
                ->where('name', 'like', '%'.$q.'%')
                ->limit(50)
                ->get()
                ->map(fn (Folder $f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'path' => $f->path,
                    'starred' => $f->starred_at !== null,
                ]);

            $files = File::where('user_id', $user->id)
                ->where('name', 'like', '%'.$q.'%')
                ->limit(50)
                ->get()
                ->map(fn (File $f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'extension' => $f->extension,
                    'mime_type' => $f->mime_type,
                    'size' => $f->size,
                    'starred' => $f->starred_at !== null,
                ]);
        }

        return Inertia::render('drive/Search', [
            'q' => $q,
            'folders' => $folders,
            'files' => $files,
        ]);
    }
}
