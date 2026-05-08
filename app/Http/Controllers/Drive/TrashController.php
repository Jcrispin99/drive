<?php

namespace App\Http\Controllers\Drive;

use App\Actions\Drive\PurgeAction;
use App\Actions\Drive\RestoreFromTrashAction;
use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TrashController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $folders = Folder::onlyTrashed()
            ->where('user_id', $user->id)
            ->orderByDesc('deleted_at')
            ->get()
            ->map(fn (Folder $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'path' => $f->path,
                'deleted_at' => $f->deleted_at?->toIso8601String(),
            ]);

        $files = File::onlyTrashed()
            ->where('user_id', $user->id)
            ->orderByDesc('deleted_at')
            ->get()
            ->map(fn (File $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'extension' => $f->extension,
                'mime_type' => $f->mime_type,
                'size' => $f->size,
                'deleted_at' => $f->deleted_at?->toIso8601String(),
            ]);

        return Inertia::render('drive/Trash', [
            'folders' => $folders,
            'files' => $files,
        ]);
    }

    public function restore(Request $request, string $type, int $id, RestoreFromTrashAction $restore): RedirectResponse
    {
        $item = $this->findTrashed($request, $type, $id);
        $restore->execute($item);

        return back();
    }

    public function purge(Request $request, string $type, int $id, PurgeAction $purge): RedirectResponse
    {
        $item = $this->findTrashed($request, $type, $id);
        $purge->execute($item);

        return back();
    }

    public function empty(Request $request, PurgeAction $purge): RedirectResponse
    {
        $user = $request->user();

        Folder::onlyTrashed()->where('user_id', $user->id)->get()
            ->each(fn (Folder $f) => $purge->execute($f));

        File::onlyTrashed()->where('user_id', $user->id)->get()
            ->each(fn (File $f) => $purge->execute($f));

        return back();
    }

    protected function findTrashed(Request $request, string $type, int $id)
    {
        $model = match ($type) {
            'folder' => Folder::class,
            'file' => File::class,
            default => throw new NotFoundHttpException,
        };

        return $model::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }
}
