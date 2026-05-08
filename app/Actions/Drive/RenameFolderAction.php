<?php

namespace App\Actions\Drive;

use App\Models\Folder;
use Illuminate\Support\Facades\DB;

class RenameFolderAction
{
    public function execute(Folder $folder, string $newName): Folder
    {
        $newName = trim($newName);
        $oldPath = $folder->path;
        $parentPath = $folder->parent_id
            ? rtrim($folder->parent->path, '/')
            : '';
        $newPath = $parentPath.'/'.$newName;

        DB::transaction(function () use ($folder, $newName, $oldPath, $newPath): void {
            $folder->update(['name' => $newName, 'path' => $newPath]);

            $this->updateDescendantPaths($folder->user_id, $oldPath, $newPath);
        });

        return $folder->refresh();
    }

    protected function updateDescendantPaths(int $userId, string $oldPath, string $newPath): void
    {
        $oldPrefix = $oldPath.'/';
        $newPrefix = $newPath.'/';

        DB::table('folders')
            ->where('user_id', $userId)
            ->where('path', 'like', $oldPrefix.'%')
            ->update([
                'path' => DB::raw(sprintf(
                    "CONCAT(%s, SUBSTR(path, %d))",
                    DB::getPdo()->quote($newPrefix),
                    strlen($oldPrefix) + 1
                )),
            ]);
    }
}
