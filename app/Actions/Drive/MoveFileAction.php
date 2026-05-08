<?php

namespace App\Actions\Drive;

use App\Models\File;
use App\Models\Folder;
use InvalidArgumentException;

class MoveFileAction
{
    public function execute(File $file, ?Folder $newFolder): File
    {
        if ($newFolder && $newFolder->user_id !== $file->user_id) {
            throw new InvalidArgumentException('Cannot move file to another user.');
        }

        $file->update(['folder_id' => $newFolder?->id]);

        return $file->refresh();
    }
}
