<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\Share;
use App\Models\User;

class FolderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Folder $folder): bool
    {
        if ($folder->user_id === $user->id) {
            return true;
        }

        return Share::query()
            ->where('shareable_type', Folder::class)
            ->where('shareable_id', $folder->id)
            ->where('shared_with', $user->id)
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Folder $folder): bool
    {
        if ($folder->user_id === $user->id) {
            return true;
        }

        return Share::query()
            ->where('shareable_type', Folder::class)
            ->where('shareable_id', $folder->id)
            ->where('shared_with', $user->id)
            ->where('permission', 'edit')
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $folder->user_id === $user->id;
    }

    public function restore(User $user, Folder $folder): bool
    {
        return $folder->user_id === $user->id;
    }

    public function forceDelete(User $user, Folder $folder): bool
    {
        return $folder->user_id === $user->id;
    }
}
