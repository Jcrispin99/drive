<?php

namespace App\Actions\Drive;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeleteToTrashAction
{
    public function execute(Model $item): void
    {
        DB::transaction(function () use ($item): void {
            if ($item instanceof Folder) {
                $this->softDeleteDescendants($item);
            }

            $item->delete();
        });
    }

    protected function softDeleteDescendants(Folder $folder): void
    {
        $descendantIds = Folder::where('user_id', $folder->user_id)
            ->where('path', 'like', $folder->path.'/%')
            ->pluck('id');

        if ($descendantIds->isNotEmpty()) {
            Folder::whereIn('id', $descendantIds)->delete();
        }

        File::where('user_id', $folder->user_id)
            ->where(function ($q) use ($folder, $descendantIds): void {
                $q->where('folder_id', $folder->id)
                    ->orWhereIn('folder_id', $descendantIds);
            })
            ->delete();
    }
}
