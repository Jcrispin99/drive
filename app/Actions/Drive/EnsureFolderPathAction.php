<?php

namespace App\Actions\Drive;

use App\Models\Folder;
use App\Models\User;

class EnsureFolderPathAction
{
    public function __construct(
        protected CreateFolderAction $createFolder,
    ) {}

    /**
     * Walk a path like "Docs/2026/Q1" relative to $root, creating
     * any missing folders along the way. Returns the leaf folder
     * (or $root if path is empty).
     */
    public function execute(User $user, ?Folder $root, string $relativePath): ?Folder
    {
        $segments = array_values(array_filter(
            explode('/', str_replace('\\', '/', $relativePath)),
            fn (string $s) => $s !== '' && $s !== '.' && $s !== '..',
        ));

        if ($segments === []) {
            return $root;
        }

        $current = $root;

        foreach ($segments as $segment) {
            $existing = Folder::where('user_id', $user->id)
                ->where('parent_id', $current?->id)
                ->where('name', $segment)
                ->first();

            $current = $existing ?? $this->createFolder->execute($user, $segment, $current);
        }

        return $current;
    }
}
