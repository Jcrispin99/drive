<?php

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DriveController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('drive/Index', [
            'folder' => null,
            'breadcrumbs' => [],
            'folders' => $this->folderRows(
                Folder::where('user_id', $user->id)
                    ->whereNull('parent_id')
                    ->orderBy('name')
                    ->get()
            ),
            'files' => $this->fileRows(
                File::where('user_id', $user->id)
                    ->whereNull('folder_id')
                    ->orderBy('name')
                    ->get()
            ),
        ]);
    }

    public function show(Request $request, Folder $folder): Response
    {
        Gate::authorize('view', $folder);

        return Inertia::render('drive/Folder', [
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'path' => $folder->path,
                'parent_id' => $folder->parent_id,
                'starred' => $folder->starred_at !== null,
            ],
            'breadcrumbs' => $this->breadcrumbsFor($folder),
            'folders' => $this->folderRows($folder->children()->orderBy('name')->get()),
            'files' => $this->fileRows($folder->files()->orderBy('name')->get()),
        ]);
    }

    /**
     * @param  Collection<int, Folder>  $folders
     */
    protected function folderRows(Collection $folders): array
    {
        return $folders->map(fn (Folder $f) => [
            'id' => $f->id,
            'name' => $f->name,
            'path' => $f->path,
            'starred' => $f->starred_at !== null,
            'updated_at' => $f->updated_at?->toIso8601String(),
        ])->all();
    }

    /**
     * @param  Collection<int, File>  $files
     */
    protected function fileRows(Collection $files): array
    {
        return $files->map(fn (File $f) => [
            'id' => $f->id,
            'name' => $f->name,
            'extension' => $f->extension,
            'mime_type' => $f->mime_type,
            'size' => $f->size,
            'starred' => $f->starred_at !== null,
            'updated_at' => $f->updated_at?->toIso8601String(),
        ])->all();
    }

    protected function breadcrumbsFor(Folder $folder): array
    {
        $segments = array_values(array_filter(explode('/', $folder->path)));
        if ($segments === []) {
            return [];
        }

        $cumulativePaths = [];
        $cursor = '';
        foreach ($segments as $segment) {
            $cursor .= '/'.$segment;
            $cumulativePaths[] = $cursor;
        }

        $folders = Folder::where('user_id', $folder->user_id)
            ->whereIn('path', $cumulativePaths)
            ->get()
            ->keyBy('path');

        return collect($cumulativePaths)
            ->map(fn (string $p) => [
                'id' => $folders[$p]?->id,
                'name' => $folders[$p]?->name,
            ])
            ->filter(fn ($b) => $b['id'] !== null)
            ->values()
            ->all();
    }
}
