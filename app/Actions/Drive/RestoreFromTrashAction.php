<?php

namespace App\Actions\Drive;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RestoreFromTrashAction
{
    public function execute(Model $item): void
    {
        DB::transaction(function () use ($item): void {
            $item->restore();

            if ($item instanceof Folder) {
                $this->restoreDescendants($item);
            }
        });
    }

    protected function restoreDescendants(Folder $folder): void
    {
        $descendantIds = Folder::onlyTrashed()
            ->where('user_id', $folder->user_id)
            ->where('path', 'like', $folder->path.'/%')
            ->pluck('id');

        if ($descendantIds->isNotEmpty()) {
            Folder::onlyTrashed()->whereIn('id', $descendantIds)->restore();
        }

        File::onlyTrashed()
            ->where('user_id', $folder->user_id)
            ->where(function ($q) use ($folder, $descendantIds): void {
                $q->where('folder_id', $folder->id)
                    ->orWhereIn('folder_id', $descendantIds);
            })
            ->restore();
    }
}
