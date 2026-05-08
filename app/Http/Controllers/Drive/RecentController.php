<?php

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RecentController extends Controller
{
    public function index(Request $request): Response
    {
        $files = File::where('user_id', $request->user()->id)
            ->orderByDesc(DB::raw('COALESCE(last_accessed_at, created_at)'))
            ->limit(50)
            ->get()
            ->map(fn (File $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'extension' => $f->extension,
                'mime_type' => $f->mime_type,
                'size' => $f->size,
                'folder_id' => $f->folder_id,
                'starred' => $f->starred_at !== null,
                'last_accessed_at' => $f->last_accessed_at?->toIso8601String(),
            ]);

        return Inertia::render('drive/Recent', [
            'files' => $files,
        ]);
    }
}
