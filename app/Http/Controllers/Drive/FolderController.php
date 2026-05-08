<?php

namespace App\Http\Controllers\Drive;

use App\Actions\Drive\CreateFolderAction;
use App\Actions\Drive\DeleteToTrashAction;
use App\Actions\Drive\MoveFolderAction;
use App\Actions\Drive\RenameFolderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Drive\MoveRequest;
use App\Http\Requests\Drive\StoreFolderRequest;
use App\Http\Requests\Drive\UpdateFolderRequest;
use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class FolderController extends Controller
{
    public function store(StoreFolderRequest $request, CreateFolderAction $create): RedirectResponse
    {
        $parent = $request->parent_id
            ? Folder::where('user_id', $request->user()->id)->findOrFail($request->parent_id)
            : null;

        $create->execute($request->user(), $request->validated('name'), $parent);

        return back();
    }

    public function update(UpdateFolderRequest $request, Folder $folder, RenameFolderAction $rename): RedirectResponse
    {
        $rename->execute($folder, $request->validated('name'));

        return back();
    }

    public function move(MoveRequest $request, Folder $folder, MoveFolderAction $move): RedirectResponse
    {
        $newParent = $request->parent_id
            ? Folder::where('user_id', $request->user()->id)->findOrFail($request->parent_id)
            : null;

        $move->execute($folder, $newParent);

        return back();
    }

    public function destroy(Folder $folder, DeleteToTrashAction $trash): RedirectResponse
    {
        Gate::authorize('delete', $folder);

        $trash->execute($folder);

        return back();
    }
}
