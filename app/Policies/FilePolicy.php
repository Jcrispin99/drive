<?php

namespace App\Policies;

use App\Models\File;
use App\Models\Folder;
use App\Models\Share;
use App\Models\User;

class FilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, File $file): bool
    {
        if ($file->user_id === $user->id) {
            return true;
        }

        return $this->hasShare($user, $file)
            || $this->ancestorFolderShared($user, $file, 'view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, File $file): bool
    {
        if ($file->user_id === $user->id) {
            return true;
        }

        return $this->hasShare($user, $file, 'edit')
            || $this->ancestorFolderShared($user, $file, 'edit');
    }

    public function delete(User $user, File $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function restore(User $user, File $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function forceDelete(User $user, File $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function download(User $user, File $file): bool
    {
        return $this->view($user, $file);
    }

    protected function hasShare(User $user, File $file, ?string $permission = null): bool
    {
        return Share::query()
            ->where('shareable_type', File::class)
            ->where('shareable_id', $file->id)
            ->where('shared_with', $user->id)
            ->when($permission, fn ($q) => $q->where('permission', $permission))
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    protected function ancestorFolderShared(User $user, File $file, string $permission): bool
    {
        if (! $file->folder_id) {
            return false;
        }

        $folder = Folder::find($file->folder_id);
        if (! $folder) {
            return false;
        }

        $ancestorPaths = [];
        $segments = array_filter(explode('/', $folder->path));
        $cursor = '';
        foreach ($segments as $segment) {
            $cursor .= '/'.$segment;
            $ancestorPaths[] = $cursor;
        }

        $ancestorIds = Folder::where('user_id', $folder->user_id)
            ->whereIn('path', $ancestorPaths)
            ->pluck('id');

        return Share::query()
            ->where('shareable_type', Folder::class)
            ->whereIn('shareable_id', $ancestorIds)
            ->where('shared_with', $user->id)
            ->when($permission === 'view', fn ($q) => $q->whereIn('permission', ['view', 'edit']))
            ->when($permission === 'edit', fn ($q) => $q->where('permission', 'edit'))
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
