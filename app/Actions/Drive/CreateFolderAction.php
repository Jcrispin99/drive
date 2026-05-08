<?php

namespace App\Actions\Drive;

use App\Models\Folder;
use App\Models\User;

class CreateFolderAction
{
    public function execute(User $user, string $name, ?Folder $parent = null): Folder
    {
        $name = trim($name);
        $parentPath = $parent ? rtrim($parent->path, '/') : '';

        return Folder::create([
            'user_id' => $user->id,
            'parent_id' => $parent?->id,
            'name' => $name,
            'path' => $parentPath.'/'.$name,
        ]);
    }
}
