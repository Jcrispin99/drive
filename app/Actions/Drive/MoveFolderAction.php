<?php

namespace App\Actions\Drive;

use App\Models\Folder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MoveFolderAction
{
    public function execute(Folder $folder, ?Folder $newParent): Folder
    {
        if ($newParent && $newParent->user_id !== $folder->user_id) {
            throw new InvalidArgumentException('Cannot move folder to another user.');
        }

        if ($newParent && $this->wouldCreateCycle($folder, $newParent)) {
            throw new InvalidArgumentException('Cannot move a folder into one of its descendants.');
        }

        $oldPath = $folder->path;
        $parentPath = $newParent ? rtrim($newParent->path, '/') : '';
        $newPath = $parentPath.'/'.$folder->name;

        DB::transaction(function () use ($folder, $newParent, $oldPath, $newPath): void {
            $folder->update([
                'parent_id' => $newParent?->id,
                'path' => $newPath,
            ]);

            $oldPrefix = $oldPath.'/';
            $newPrefix = $newPath.'/';

            DB::table('folders')
                ->where('user_id', $folder->user_id)
                ->where('path', 'like', $oldPrefix.'%')
                ->update([
                    'path' => DB::raw(sprintf(
                        "CONCAT(%s, SUBSTR(path, %d))",
                        DB::getPdo()->quote($newPrefix),
                        strlen($oldPrefix) + 1
                    )),
                ]);
        });

        return $folder->refresh();
    }

    protected function wouldCreateCycle(Folder $folder, Folder $target): bool
    {
        if ($target->id === $folder->id) {
            return true;
        }

        return str_starts_with($target->path.'/', $folder->path.'/');
    }
}
